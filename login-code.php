<?php
/**
 * Sign-in, second step: the 6-digit code from the authenticator app, or one
 * of the backup codes. Only reached straight after a correct password (see
 * auth_attempt()); wrong codes count towards the same lock-out as wrong
 * passwords.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/twofactor.php';

auth_start_session();
if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}
if (empty($_SESSION['twofactor'])) {
    header('Location: ' . url('login.php'));
    exit;
}

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $code = is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '';
    if (!csrf_valid()) {
        $error = 'This form expired. Please try again.';
    } elseif ($code === '') {
        $error = 'Please enter the code.';
    } else {
        $result = twofactor_verify_login($code);
        if ($result === 'ok') {
            $next = $_SESSION['login_next'] ?? 'admin/dashboard.php';
            unset($_SESSION['login_next']);
            header('Location: ' . url($next));
            exit;
        }
        if ($result === 'expired') {
            flash_set('info', 'That took a little too long. Please sign in again.');
            header('Location: ' . url('login.php'));
            exit;
        }
        $error = $result === 'locked'
            ? 'Too many attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.'
            : 'That code is not correct. Check the app shows ' . SITE_NAME . ' and try the newest code.';
    }
}

$pageTitle       = 'Sign in: code';
$pageDescription = SITE_TAGLINE;
$noindex         = true;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="login-page">
    <div class="login-card">
        <h1 class="login-card__title">Enter your code</h1>
        <p class="login-card__intro">Open your authenticator app and type the 6-digit code for <?= e(SITE_NAME) ?>.</p>

<?php if ($error !== null): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

        <form method="post" class="login-form">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="code">Code</label>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="12" required autofocus aria-describedby="code-help">
                <p class="form-note" id="code-help">Lost your phone? Type one of your backup codes instead, for example abcd-efgh.</p>
            </div>
            <button class="btn btn--primary" type="submit">Sign in</button>
        </form>
        <p class="login-card__link"><a href="<?= e(url('login.php')) ?>">Start again</a></p>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
