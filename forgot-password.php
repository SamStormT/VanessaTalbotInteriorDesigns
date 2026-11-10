<?php
/**
 * Admin "forgot password": asks for an email address and, if it belongs to an
 * active account, emails a single-use reset link. The response is identical
 * whether or not the account exists.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms.php';

if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = null;
$sent  = false;
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

    if (!csrf_valid()) {
        $error = 'This form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        password_reset_request($email);
        $sent = true;
    }
}

$pageTitle       = 'Forgot password';
$pageDescription = SITE_TAGLINE;
$noindex         = true;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="login-page">
    <div class="login-card">
        <h1 class="login-card__title">Forgot password</h1>
<?php if ($sent): ?>
        <p class="admin-alert admin-alert--success" role="status">If an account uses that email address, we have sent it a link to choose a new password. The link expires in <?= PASSWORD_RESET_MINUTES ?> minutes.</p>
        <p>Nothing arrived? Check your spam folder, or ask the website administrator for help.</p>
        <p><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
<?php else: ?>
        <p class="login-card__intro">Enter the email address you sign in with, and we will email you a link to choose a new password.</p>
<?php if ($error !== null): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></p>
<?php endif; ?>
        <form method="post" class="login-form">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>">
            </div>
            <button class="btn btn--primary" type="submit">Send reset link</button>
        </form>
        <p class="login-card__link"><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
<?php endif; ?>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
