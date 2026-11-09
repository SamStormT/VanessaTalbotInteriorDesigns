<?php
/**
 * Admin sign-in.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms.php';

if (is_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$error = null;
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email    = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!csrf_valid()) {
        $error = 'This form expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $result = auth_attempt($email, $password);

        if ($result === 'ok') {
            $next = $_SESSION['login_next'] ?? 'admin/dashboard.php';
            unset($_SESSION['login_next']);
            header('Location: ' . url($next));
            exit;
        }

        $error = $result === 'locked'
            ? 'Too many attempts. Please wait ' . LOGIN_WINDOW_MINUTES . ' minutes and try again.'
            : 'That email or password is not correct.';
    }
}

$pageTitle       = 'Sign in';
$pageDescription = SITE_TAGLINE;
$noindex         = true;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="login-page">
    <div class="login-card">
        <h1 class="login-card__title">Sign in</h1>
        <p class="login-card__intro">Website administration</p>

        <?= cms_flash() ?>
<?php if ($error !== null): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

        <form method="post" class="login-form">
            <?= csrf_field() ?>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>">
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn--primary" type="submit">Sign in</button>
        </form>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
