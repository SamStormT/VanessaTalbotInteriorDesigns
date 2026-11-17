<?php
/**
 * My account: for everyone signed in, whatever their role.
 *
 *   - Your details: first name and surname (the email address and role are
 *     changed by a SuperAdmin, under Users)
 *   - Change your password (needs the current one; signs you out everywhere
 *     else, and emails you)
 *   - Two-step sign-in: switch on with an authenticator app (QR code or key),
 *     get backup codes, make new ones, or switch it off (needs the password)
 *   - Your recent sign-ins, to spot anything you don't recognise
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/twofactor.php';
require_once INCLUDES_PATH . '/mail.php';

require_admin();
$me   = current_admin();
$id   = (int) $me['AdminUserID'];
$self = url('admin/account.php');
$user = db_row('SELECT AdminUserID, Name, Surname, Email, Role, PasswordHash, TwoFactorEnabledDate, TwoFactorRecovery FROM AdminUsers WHERE AdminUserID = ?', [$id]);

$errors = [];
$back = static function (string $type, string $message, string $anchor = '') use ($self): never {
    flash_set($type, $message);
    header('Location: ' . $self . $anchor);
    exit;
};
$passwordOk = static fn(): bool => password_verify(is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '', $user['PasswordHash']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $do = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
    if (!csrf_valid()) {
        $back('error', 'This page expired. Please try again.');
    }

    if ($do === 'details') {
        $name    = trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', is_string($_POST['name'] ?? null) ? $_POST['name'] : ''));
        $surname = trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', is_string($_POST['surname'] ?? null) ? $_POST['surname'] : ''));
        if ($name === '' || $surname === '' || mb_strlen($name) > 100 || mb_strlen($surname) > 100) {
            $errors['details'] = 'Please enter your first name and surname (100 characters or fewer each).';
        } else {
            db_query('UPDATE AdminUsers SET Name = ?, Surname = ? WHERE AdminUserID = ?', [$name, $surname, $id]);
            activity_log('update_own_details', 'AdminUsers', $id);
            $back('success', 'Your details have been saved.', '#details');
        }
    } elseif ($do === 'password') {
        $new    = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $repeat = is_string($_POST['repeat_password'] ?? null) ? $_POST['repeat_password'] : '';
        if (!$passwordOk()) {
            $errors['password'] = 'Your current password is not correct.';
        } elseif (mb_strlen($new) < PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Please use at least ' . PASSWORD_MIN_LENGTH . ' characters for the new password.';
        } elseif ($new !== $repeat) {
            $errors['password'] = 'The two new passwords do not match.';
        } elseif (password_verify($new, $user['PasswordHash'])) {
            $errors['password'] = 'Please choose a password different from the current one.';
        } else {
            db_query('UPDATE AdminUsers SET PasswordHash = ?, PasswordChangedDate = NOW() WHERE AdminUserID = ?', [password_hash($new, PASSWORD_DEFAULT), $id]);
            $_SESSION['login_at'] = time() + 1; // this session stays signed in; every other one ends
            activity_log('password_change', 'AdminUsers', $id, 'Changed on My account');
            mail_send_later((string) $user['Email'], 'password-changed', ['name' => (string) $user['Name'], 'loginUrl' => absolute_url('login.php')]);
            $back('success', 'Your password has been changed. You were signed out on every other device.', '#password');
        }
    } elseif ($do === '2fa_start' && empty($user['TwoFactorEnabledDate'])) {
        $_SESSION['twofactor_setup'] = twofactor_new_secret();
        header('Location: ' . $self . '#two-step');
        exit;
    } elseif ($do === '2fa_confirm' && empty($user['TwoFactorEnabledDate']) && !empty($_SESSION['twofactor_setup'])) {
        $secret = (string) $_SESSION['twofactor_setup'];
        $step   = twofactor_check($secret, is_string($_POST['code'] ?? null) ? $_POST['code'] : '', null);
        if ($step === null) {
            $errors['two-step'] = 'That code is not correct. Make sure you added the key to the app, then type the newest code it shows.';
        } else {
            $codes = twofactor_new_recovery_codes();
            db_query('UPDATE AdminUsers SET TwoFactorSecret = ?, TwoFactorEnabledDate = NOW(), TwoFactorLastStep = ?, TwoFactorRecovery = ? WHERE AdminUserID = ?', [$secret, $step, twofactor_hash_codes($codes), $id]);
            unset($_SESSION['twofactor_setup']);
            $_SESSION['twofactor_show_codes'] = $codes;
            activity_log('twofactor_on', 'AdminUsers', $id);
            $back('success', 'Two-step sign-in is on. Keep your backup codes somewhere safe.', '#two-step');
        }
    } elseif ($do === '2fa_cancel') {
        unset($_SESSION['twofactor_setup']);
        header('Location: ' . $self . '#two-step');
        exit;
    } elseif ($do === '2fa_codes' && !empty($user['TwoFactorEnabledDate'])) {
        if (!$passwordOk()) {
            $errors['two-step'] = 'Your password is not correct.';
        } else {
            $codes = twofactor_new_recovery_codes();
            db_query('UPDATE AdminUsers SET TwoFactorRecovery = ? WHERE AdminUserID = ?', [twofactor_hash_codes($codes), $id]);
            $_SESSION['twofactor_show_codes'] = $codes;
            activity_log('twofactor_new_codes', 'AdminUsers', $id);
            $back('success', 'New backup codes made. The old ones no longer work.', '#two-step');
        }
    } elseif ($do === '2fa_off' && !empty($user['TwoFactorEnabledDate'])) {
        if (!$passwordOk()) {
            $errors['two-step'] = 'Your password is not correct.';
        } else {
            db_query('UPDATE AdminUsers SET TwoFactorSecret = NULL, TwoFactorEnabledDate = NULL, TwoFactorLastStep = NULL, TwoFactorRecovery = NULL WHERE AdminUserID = ?', [$id]);
            activity_log('twofactor_off', 'AdminUsers', $id, 'Switched off on My account');
            $back('success', 'Two-step sign-in is off. Only your password protects your account now.', '#two-step');
        }
    }
}

$setupSecret = empty($user['TwoFactorEnabledDate']) ? ($_SESSION['twofactor_setup'] ?? null) : null;
$showCodes   = $_SESSION['twofactor_show_codes'] ?? null;
unset($_SESSION['twofactor_show_codes']);
$signIns = db_all('SELECT AttemptDate, Successful, IPAddress FROM LoginAttempts WHERE Email = ? ORDER BY AttemptDate DESC LIMIT 10', [$user['Email']]);

$pageTitle = 'My account';
$layout    = 'admin';
require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content account">
    <?= cms_flash() ?>

    <section class="admin-panel" id="details" aria-labelledby="details-title">
        <h2 class="admin-panel__title" id="details-title">Your details</h2>
<?php if (isset($errors['details'])): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($errors['details']) ?></p>
<?php endif; ?>
        <form method="post" class="admin-form">
            <?= csrf_field() ?><input type="hidden" name="do" value="details">
            <div class="admin-field"><label for="name">First name</label><input id="name" name="name" type="text" maxlength="100" required autocomplete="given-name" value="<?= e($user['Name']) ?>"></div>
            <div class="admin-field"><label for="surname">Surname</label><input id="surname" name="surname" type="text" maxlength="100" required autocomplete="family-name" value="<?= e($user['Surname']) ?>"></div>
            <dl class="admin-fields">
                <div><dt>Email</dt><dd><?= e($user['Email']) ?></dd></div>
                <div><dt>Role</dt><dd><?= e($user['Role']) ?> <span class="admin-muted">(a SuperAdmin changes email addresses and roles, under Users)</span></dd></div>
            </dl>
            <div class="admin-actions"><button class="btn btn--primary" type="submit">Save details</button></div>
        </form>
    </section>

    <section class="admin-panel" id="password" aria-labelledby="password-title">
        <h2 class="admin-panel__title" id="password-title">Change your password</h2>
<?php if (isset($errors['password'])): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($errors['password']) ?></p>
<?php endif; ?>
        <form method="post" class="admin-form">
            <?= csrf_field() ?><input type="hidden" name="do" value="password">
            <input type="text" name="username" value="<?= e($user['Email']) ?>" autocomplete="username" hidden>
            <div class="admin-field"><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" required autocomplete="current-password"></div>
            <div class="admin-field"><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" aria-describedby="new-password-help"><p class="admin-help" id="new-password-help">At least <?= PASSWORD_MIN_LENGTH ?> characters. A few unrelated words make a strong password that's easy to remember.</p></div>
            <div class="admin-field"><label for="repeat-password">New password again</label><input id="repeat-password" name="repeat_password" type="password" required autocomplete="new-password"></div>
            <div class="admin-actions"><button class="btn btn--primary" type="submit">Change password</button></div>
        </form>
    </section>

    <section class="admin-panel" id="two-step" aria-labelledby="two-step-title">
        <h2 class="admin-panel__title" id="two-step-title">Two-step sign-in</h2>
<?php if (isset($errors['two-step'])): ?>
        <p class="admin-alert admin-alert--error" role="alert"><?= e($errors['two-step']) ?></p>
<?php endif; ?>
<?php if ($showCodes): ?>
        <div class="account-codes" role="status">
            <p><strong>Your backup codes.</strong> Each works once, if you ever don't have your phone. Print them or save them somewhere safe now: they won't be shown again.</p>
            <ul class="account-codes__list">
<?php foreach ($showCodes as $code): ?>
                <li><code><?= e($code) ?></code></li>
<?php endforeach; ?>
            </ul>
            <p class="admin-actions"><button type="button" class="btn btn--outline btn--small" data-print>Print the codes</button></p>
        </div>
<?php endif; ?>
<?php if (!empty($user['TwoFactorEnabledDate'])): ?>
        <p><span class="admin-badge admin-badge--published">On</span> since <?= e(cms_format_date($user['TwoFactorEnabledDate'])) ?>. Signing in asks for a code from your authenticator app after your password. <?= twofactor_codes_left($user['TwoFactorRecovery']) ?> backup codes left.</p>
        <form method="post" class="admin-form account-2fa-off">
            <?= csrf_field() ?>
            <div class="admin-field"><label for="confirm-password">Your password, to make new backup codes or switch it off</label><input id="confirm-password" name="current_password" type="password" required autocomplete="current-password"></div>
            <div class="admin-actions">
                <button class="btn btn--outline" type="submit" name="do" value="2fa_codes">Make new backup codes</button>
                <button class="btn btn--outline" type="submit" name="do" value="2fa_off" data-confirm="Switch off two-step sign-in? Only your password will protect your account.">Switch it off</button>
            </div>
        </form>
<?php elseif ($setupSecret): ?>
        <ol class="account-steps">
            <li>Install an authenticator app on your phone if you don't have one: Google Authenticator or Microsoft Authenticator (free).</li>
            <li>In the app, add an account and scan this code:
                <div class="account-qr" data-qr="<?= e(twofactor_uri($setupSecret, (string) $user['Email'])) ?>" role="img" aria-label="QR code for your authenticator app"></div>
                Can't scan it? Choose "enter a setup key" and type: <code class="account-key"><?= e(trim(chunk_split($setupSecret, 4, ' '))) ?></code></li>
            <li>Type the 6-digit code the app now shows:</li>
        </ol>
        <form method="post" class="admin-form">
            <?= csrf_field() ?><input type="hidden" name="do" value="2fa_confirm">
            <div class="admin-field"><label for="setup-code">Code from the app</label><input id="setup-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required></div>
            <div class="admin-actions">
                <button class="btn btn--primary" type="submit">Switch on</button>
                <button class="btn btn--outline" type="submit" name="do" value="2fa_cancel" formnovalidate>Cancel</button>
            </div>
        </form>
<?php else: ?>
        <p><span class="admin-badge">Off</span> Only your password protects your account. With two-step sign-in, signing in also needs a code from an app on your phone, so a stolen password alone isn't enough.<?= $user['Role'] === 'SuperAdmin' ? ' <strong>Strongly recommended for a SuperAdmin.</strong>' : '' ?></p>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="do" value="2fa_start">
            <button class="btn btn--primary" type="submit">Set up two-step sign-in</button>
        </form>
<?php endif; ?>
    </section>

    <section class="admin-panel" aria-labelledby="signins-title">
        <h2 class="admin-panel__title" id="signins-title">Your recent sign-ins</h2>
        <p class="admin-help">Including attempts with a wrong password or code. If you see something you don't recognise, change your password and tell the SuperAdmin.</p>
<?php if ($signIns === []): ?>
        <p class="admin-muted">None recorded.</p>
<?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th scope="col">When</th><th scope="col">Result</th><th scope="col">Internet address</th></tr></thead>
                <tbody>
<?php foreach ($signIns as $attempt): ?>
                    <tr><td><?= e(cms_format_date($attempt['AttemptDate'])) ?></td><td><?= $attempt['Successful'] ? 'Signed in (password)' : '<strong>Failed</strong>' ?></td><td><?= e((string) $attempt['IPAddress']) ?></td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php endif; ?>
    </section>
</main>
<script src="<?= e(asset('js/lib/qrcode.js')) ?>" defer></script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
