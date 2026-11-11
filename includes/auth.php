<?php
/**
 * Authentication, sessions, CSRF protection, activity logging and the
 * permission middleware for the admin area.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/permissions.php';

const SESSION_IDLE_SECONDS     = 1800;  // signed out after 30 minutes without activity
const SESSION_ABSOLUTE_SECONDS = 28800; // signed out after 8 hours regardless
const LOGIN_WINDOW_MINUTES     = 15;
const LOGIN_MAX_PER_EMAIL      = 5;
const LOGIN_MAX_PER_IP         = 20;
const PASSWORD_MIN_LENGTH      = 12;
const PASSWORD_RESET_MINUTES   = 60;
const ACCOUNT_INVITE_MINUTES   = 4320; // 3 days to set a password on a new account
const PASSWORD_RESET_PER_USER  = 3;   // reset emails per account per hour
const PASSWORD_RESET_PER_IP    = 10;  // reset emails per IP address per hour

// Verified when an email is unknown, so response time doesn't reveal which accounts exist.
const DUMMY_PASSWORD_HASH = '$2y$10$YK.O0ZXW64DjRwMv6jkEMuW9avWi9z4dEvmAVjflVm2kAmneof.Xu';

// ---------------------------------------------------------------------------
// Sessions
// ---------------------------------------------------------------------------

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_ABSOLUTE_SECONDS);

    session_name('VTIDSESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
        'secure'   => $https || APP_ENV === 'production',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: same-origin');
        header('Cache-Control: no-store');
    }
}

/** Clear the current session and begin a fresh one under a new ID. */
function auth_reset_session(): void
{
    auth_start_session();
    $_SESSION = [];
    session_regenerate_id(true);
    unset($GLOBALS['current_admin_cache']);
}

function flash_set(string $type, string $message): void
{
    auth_start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    auth_start_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    auth_start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    auth_start_session();
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $submitted);
}

// ---------------------------------------------------------------------------
// Activity log (user, action, table, record, timestamp)
// ---------------------------------------------------------------------------

function activity_log(string $action, ?string $table = null, ?int $recordId = null, ?string $details = null, ?int $adminId = null): void
{
    $adminId ??= isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;

    db_query(
        'INSERT INTO ActivityLog (AdminUserID, Action, EntityType, EntityID, Details, IPAddress) VALUES (?, ?, ?, ?, ?, ?)',
        [$adminId, $action, $table, $recordId, $details, client_ip()]
    );
}

/** Activity by a website visitor (contact form, newsletter), never credited to a signed-in user. */
function activity_log_visitor(string $action, string $table, int $recordId, ?string $details = null): void
{
    db_query(
        'INSERT INTO ActivityLog (AdminUserID, Action, EntityType, EntityID, Details, IPAddress) VALUES (NULL, ?, ?, ?, ?, ?)',
        [$action, $table, $recordId, $details, client_ip()]
    );
}

// ---------------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------------

function login_is_locked(string $email, string $ip): bool
{
    $window = 'AttemptDate >= NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE';

    // A successful login clears the count for that address.
    $emailFailures = (int) db_value(
        "SELECT COUNT(*) FROM LoginAttempts
         WHERE Email = ? AND Successful = 0 AND $window
           AND AttemptDate >= COALESCE((SELECT MAX(s.AttemptDate) FROM LoginAttempts s WHERE s.Email = ? AND s.Successful = 1), '1970-01-01')",
        [$email, $email]
    );
    if ($emailFailures >= LOGIN_MAX_PER_EMAIL) {
        return true;
    }

    return (int) db_value("SELECT COUNT(*) FROM LoginAttempts WHERE IPAddress = ? AND Successful = 0 AND $window", [$ip]) >= LOGIN_MAX_PER_IP;
}

function login_record_attempt(string $email, string $ip, bool $successful): void
{
    db_query('INSERT INTO LoginAttempts (Email, IPAddress, Successful) VALUES (?, ?, ?)', [$email, $ip, (int) $successful]);
}

/**
 * Try to sign in. Returns 'ok', 'invalid' or 'locked'.
 * Unknown emails, wrong passwords and inactive accounts are all 'invalid'.
 */
function auth_attempt(string $email, string $password): string
{
    $email = strtolower(trim($email));
    $ip    = client_ip();

    if (login_is_locked($email, $ip)) {
        return 'locked';
    }

    $user  = db_row('SELECT AdminUserID, PasswordHash, Status FROM AdminUsers WHERE Email = ?', [$email]);
    $valid = password_verify($password, $user['PasswordHash'] ?? DUMMY_PASSWORD_HASH);

    if ($user === null || !$valid || $user['Status'] !== 'Active') {
        login_record_attempt($email, $ip, false);
        return 'invalid';
    }

    if (password_needs_rehash($user['PasswordHash'], PASSWORD_DEFAULT)) {
        db_query('UPDATE AdminUsers SET PasswordHash = ? WHERE AdminUserID = ?', [password_hash($password, PASSWORD_DEFAULT), $user['AdminUserID']]);
    }

    login_record_attempt($email, $ip, true);

    // A new session ID and CSRF token at the moment of sign-in stop session fixation.
    $next = $_SESSION['login_next'] ?? null;
    auth_reset_session();
    $_SESSION['admin_id']      = (int) $user['AdminUserID'];
    $_SESSION['login_at']      = time();
    $_SESSION['last_activity'] = time();
    if ($next !== null) {
        $_SESSION['login_next'] = $next;
    }

    db_query('UPDATE AdminUsers SET LastLogin = NOW() WHERE AdminUserID = ?', [$user['AdminUserID']]);
    activity_log('login', 'AdminUsers', (int) $user['AdminUserID']);

    return 'ok';
}

function auth_logout(): void
{
    auth_start_session();
    if (isset($_SESSION['admin_id'])) {
        activity_log('logout', 'AdminUsers', (int) $_SESSION['admin_id']);
    }
    auth_reset_session();
}

// ---------------------------------------------------------------------------
// Current user
// ---------------------------------------------------------------------------

/** The signed-in admin, re-checked against the database on every request. */
function current_admin(): ?array
{
    if (array_key_exists('current_admin_cache', $GLOBALS)) {
        return $GLOBALS['current_admin_cache'];
    }

    auth_start_session();
    $GLOBALS['current_admin_cache'] = null;

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    $now = time();
    if ($now - (int) $_SESSION['last_activity'] > SESSION_IDLE_SECONDS || $now - (int) $_SESSION['login_at'] > SESSION_ABSOLUTE_SECONDS) {
        activity_log('session_timeout', 'AdminUsers', (int) $_SESSION['admin_id']);
        auth_reset_session();
        flash_set('info', 'You were signed out after a period of inactivity. Please sign in again.');
        $GLOBALS['current_admin_cache'] = null;
        return null;
    }

    $user = db_row(
        'SELECT AdminUserID, Name, Surname, Email, Role, Status, PasswordChangedDate FROM AdminUsers WHERE AdminUserID = ?',
        [(int) $_SESSION['admin_id']]
    );

    // A deactivated account or a password change ends every existing session.
    $passwordChanged = $user !== null && $user['PasswordChangedDate'] !== null
        && strtotime($user['PasswordChangedDate']) > (int) $_SESSION['login_at'];

    if ($user === null || $user['Status'] !== 'Active' || $passwordChanged) {
        auth_reset_session();
        $GLOBALS['current_admin_cache'] = null;
        return null;
    }

    $_SESSION['last_activity'] = $now;
    return $GLOBALS['current_admin_cache'] = $user;
}

function is_logged_in(): bool
{
    return current_admin() !== null;
}

function can(string $permission): bool
{
    $admin = current_admin();
    return $admin !== null && role_can($admin['Role'], $permission);
}

// ---------------------------------------------------------------------------
// Permission middleware
// ---------------------------------------------------------------------------

function require_admin(): void
{
    if (is_logged_in()) {
        return;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && str_starts_with(current_path(), 'admin/')) {
        $_SESSION['login_next'] = current_path();
    }
    header('Location: ' . url('login.php'));
    exit;
}

/** Stop the request with a full admin-layout message page. */
function admin_stop(int $status, string $title, string $message): never
{
    http_response_code($status);
    $pageTitle = $title;
    $layout    = 'admin';
    require INCLUDES_PATH . '/header.php';
    echo '<main id="main-content" class="admin-content"><div class="admin-empty">'
       . '<h2 class="admin-empty__title">' . e($title) . '</h2><p>' . e($message) . '</p>'
       . '<a class="btn btn--primary btn--small" href="' . e(url('admin/dashboard.php')) . '">Back to dashboard</a>'
       . '</div></main>';
    require INCLUDES_PATH . '/footer.php';
    exit;
}

/** Sign-in, permission and (for POST) CSRF checks. Call first on every admin page. */
function admin_guard(string $permission): void
{
    require_admin();

    if (!can($permission)) {
        activity_log('access_denied', null, null, $permission . ' at ' . current_path());
        admin_stop(403, 'Access denied', 'Your account does not have permission to use this area.');
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_valid()) {
        admin_stop(403, 'Please try again', 'This form expired for security reasons. Go back, refresh the page and submit it again.');
    }
}

// ---------------------------------------------------------------------------
// Password resets
// ---------------------------------------------------------------------------

/**
 * "Forgot password": email a reset link if the address belongs to an active
 * account. Returns nothing, and the page says the same thing either way, so
 * the form can't be used to discover which accounts exist. The email is sent
 * after the page has been delivered, so timing doesn't reveal it either.
 */
function password_reset_request(string $email): void
{
    $email = strtolower(trim($email));
    $user  = db_row("SELECT AdminUserID, Name, Email FROM AdminUsers WHERE Email = ? AND Status = 'Active'", [$email]);

    if ($user === null) {
        return;
    }

    $recentForUser = (int) db_value('SELECT COUNT(*) FROM PasswordResets WHERE AdminUserID = ? AND CreatedDate >= NOW() - INTERVAL 1 HOUR', [$user['AdminUserID']]);
    $recentFromIp  = (int) db_value('SELECT COUNT(*) FROM PasswordResets WHERE RequestIP = ? AND CreatedDate >= NOW() - INTERVAL 1 HOUR', [client_ip()]);
    if ($recentForUser >= PASSWORD_RESET_PER_USER || $recentFromIp >= PASSWORD_RESET_PER_IP) {
        activity_log('password_reset_limited', 'AdminUsers', (int) $user['AdminUserID'], null, (int) $user['AdminUserID']);
        return;
    }

    $token = password_reset_create((int) $user['AdminUserID']);
    activity_log('password_reset_request', 'AdminUsers', (int) $user['AdminUserID'], null, (int) $user['AdminUserID']);

    require_once INCLUDES_PATH . '/mail.php';
    mail_send_later($user['Email'], 'password-reset', [
        'name'     => $user['Name'],
        'resetUrl' => absolute_url('reset-password.php?token=' . $token),
        'minutes'  => PASSWORD_RESET_MINUTES,
    ], ['to_name' => $user['Name']]);
}

/**
 * Create a reset token for an account. Returns the raw token; only its hash is
 * stored. Invitations to new users use a longer lifetime.
 */
function password_reset_create(int $adminId, int $minutes = PASSWORD_RESET_MINUTES): string
{
    db_query('UPDATE PasswordResets SET UsedDate = NOW() WHERE AdminUserID = ? AND UsedDate IS NULL', [$adminId]);

    $token = bin2hex(random_bytes(32));
    db_query(
        'INSERT INTO PasswordResets (AdminUserID, TokenHash, ExpiryDate, RequestIP)
         VALUES (?, ?, NOW() + INTERVAL ' . max(1, $minutes) . ' MINUTE, ?)',
        [$adminId, hash('sha256', $token), client_ip()]
    );

    return $token;
}

/** The unused, unexpired reset row for a raw token, or null. */
function password_reset_find(string $token): ?array
{
    return db_row(
        'SELECT r.ResetID, r.AdminUserID FROM PasswordResets r
         JOIN AdminUsers u ON u.AdminUserID = r.AdminUserID
         WHERE r.TokenHash = ? AND r.UsedDate IS NULL AND r.ExpiryDate > NOW() AND u.Status = ?',
        [hash('sha256', $token), 'Active']
    );
}

/** Set a new password from a valid token. Ends all of that user's sessions. */
function password_reset_complete(string $token, string $newPassword): bool
{
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        return false;
    }

    $reset = password_reset_find($token);
    if ($reset === null) {
        return false;
    }

    db()->beginTransaction();
    db_query(
        'UPDATE AdminUsers SET PasswordHash = ?, PasswordChangedDate = NOW() WHERE AdminUserID = ?',
        [password_hash($newPassword, PASSWORD_DEFAULT), $reset['AdminUserID']]
    );
    db_query('UPDATE PasswordResets SET UsedDate = NOW() WHERE ResetID = ?', [$reset['ResetID']]);
    activity_log('password_reset', 'AdminUsers', (int) $reset['AdminUserID'], null, (int) $reset['AdminUserID']);
    db()->commit();

    return true;
}
