<?php
/**
 * Choose a new password from an emailed reset link.
 *
 * The token arrives in the link, is moved into the session, and the page
 * reloads without it, so the token doesn't stay in the address bar, browser
 * history or any Referer header.
 *
 * Whoever is signed in on this browser is signed out first: the link may be
 * for someone else (for example a SuperAdmin testing an invitation), and
 * afterwards the new password should be used on a clean sign-in page.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/mail.php';

auth_start_session();

if (isset($_GET['token'])) {
    if (is_logged_in()) {
        auth_logout(); // logged as "logout", and starts a fresh session
    }
    $_SESSION['reset_token'] = is_string($_GET['token']) ? substr($_GET['token'], 0, 128) : '';
    header('Location: ' . url('reset-password.php'));
    exit;
}

$token  = (string) ($_SESSION['reset_token'] ?? '');
$reset  = $token !== '' ? password_reset_find($token) : null;
$errors = [];

if ($reset !== null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    if (!csrf_valid()) {
        $errors['_form'] = 'This form expired. Please try again.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors['password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif (!hash_equals($password, $confirm)) {
        $errors['password_confirm'] = 'The two passwords do not match.';
    } else {
        $user = db_row('SELECT Name, Email FROM AdminUsers WHERE AdminUserID = ?', [$reset['AdminUserID']]);

        if (password_reset_complete($token, $password)) {
            unset($_SESSION['reset_token']);
            mail_send_later($user['Email'], 'password-changed', ['name' => $user['Name'], 'loginUrl' => absolute_url('login.php')], ['to_name' => $user['Name']]);
            flash_set('success', 'Your password has been changed. Please sign in with your new password.');
            header('Location: ' . url('login.php'));
            exit;
        }
        $errors['_form'] = 'This link has expired. Please ask for a new one.';
    }
}

$pageTitle       = 'Choose a new password';
$pageDescription = SITE_TAGLINE;
$noindex         = true;

$describe = static function (string $name, string $help = '') use ($errors): string {
    $ids = array_filter([$help, isset($errors[$name]) ? $name . '-error' : '']);
    return ($ids ? ' aria-describedby="' . e(implode(' ', $ids)) . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="login-page">
    <div class="login-card">
        <h1 class="login-card__title">New password</h1>
<?php if ($reset === null): ?>
        <p class="admin-alert admin-alert--error" role="alert">This link has expired or has already been used.</p>
        <p>Each link works once. Reset links expire after <?= PASSWORD_RESET_MINUTES ?> minutes, and new-account invitations after 3 days. You can ask for a new link, or ask the website administrator to send one.</p>
        <p><a class="btn btn--primary" href="<?= e(url('forgot-password.php')) ?>">Send a new link</a></p>
<?php else: ?>
        <p class="login-card__intro">Choose a new password with at least <?= PASSWORD_MIN_LENGTH ?> characters. A short phrase of several words is easy to remember and hard to guess.</p>
<?php if (isset($errors['_form'])): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($errors['_form']) ?></p>
<?php endif; ?>
        <form method="post" class="login-form" novalidate>
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="password">New password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= PASSWORD_MIN_LENGTH ?>"<?= $describe('password') ?>>
<?php if (isset($errors['password'])): ?>
                <p class="form-error" id="password-error"><?= e($errors['password']) ?></p>
<?php endif; ?>
            </div>
            <div class="form-field">
                <label for="password_confirm">Type it again</label>
                <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= $describe('password_confirm') ?>>
<?php if (isset($errors['password_confirm'])): ?>
                <p class="form-error" id="password_confirm-error"><?= e($errors['password_confirm']) ?></p>
<?php endif; ?>
            </div>
            <button class="btn btn--primary" type="submit">Save new password</button>
        </form>
<?php endif; ?>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
