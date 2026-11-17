<?php
/**
 * Marketing → Welcome Email: the email sent once to someone straight after
 * they confirm their newsletter subscription, in the newsletter's design,
 * optionally with the three newest blog articles.
 *
 *   (no parameters)  settings, preview and a test send
 *   ?edit=1          edit the wording
 *   ?raw=1           just the email, for the preview frame
 *   ?text=1          the plain-text version
 *
 * Admins edit and test it (subscribers.edit); it is switched off until
 * someone ticks "Send this welcome email".
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

$posted  = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$testing = $posted && ($_POST['do'] ?? '') === 'test';
$editing = isset($_GET['edit']) || ($posted && !$testing);

admin_guard('subscribers.' . ($editing || $testing ? 'edit' : 'view'));

$module = cms_module('newsletter_welcome');
$row    = cms_load($module, 1);
if ($row === null) {
    admin_stop(500, 'Welcome email not set up', 'Run the database setup (php database/setup.php) to add the welcome email.');
}
$page  = url('admin/welcome-email.php');
$actor = current_admin();

$sample  = ['Name' => (string) $actor['Name'], 'UnsubscribeToken' => ''];
if (isset($_GET['raw'])) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: script-src 'none'");
    echo welcome_render($row, $sample)['html'];
    exit;
}
if (isset($_GET['text'])) {
    $message = welcome_render($row, $sample);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Subject: ' . $message['subject'] . "\n\n" . $message['text'];
    exit;
}

$errors = [];
if ($testing) {
    $to = strtolower(trim(is_string($_POST['test_to'] ?? null) ? $_POST['test_to'] : ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Enter a valid email address for the test.');
    } else {
        $error = welcome_send_test($row, $to, $actor);
        flash_set($error === null ? 'success' : 'error', $error === null ? 'Test email sent to ' . $to . '.' : 'The test could not be sent: ' . $error);
    }
    header('Location: ' . $page);
    exit;
}
if ($posted) {
    $errors = cms_handle_post($module, 1, $row, $page);
}

$pageTitle = $module['title'];
$layout    = 'admin';
$activeNav = 'admin/welcome-email.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <?= cms_last_edited($row) ?>
        <?= cms_history_link($module, 1) ?>
<?php if (!$editing && can('subscribers.edit')): ?>
        <a class="btn btn--primary btn--small" href="<?= e($page) ?>?edit=1">Edit</a>
<?php endif; ?>
    </div>

<?php if ($editing): ?>
    <?= cms_render_form($module, $row, $errors, $page, $posted) ?>
<?php else: ?>
    <p class="admin-alert admin-alert--<?= (int) $row['WelcomeEnabled'] === 1 ? 'success' : 'error' ?>" role="status">
        <?= (int) $row['WelcomeEnabled'] === 1
            ? 'Switched on: everyone who confirms their subscription receives this email once.'
            : 'Switched off: nobody receives it yet. Check the wording, send yourself a test, then choose Edit and tick "Send this welcome email".' ?>
    </p>

    <dl class="admin-fields campaign-meta">
        <div><dt>Subject</dt><dd><?= e(welcome_render($row, $sample)['subject']) ?></dd></div>
    </dl>
    <p class="admin-help">Shown with your first name in place of {first_name}. Pictures use the website's address, so they appear once the website is live.
        <a href="<?= e($page . '?text=1') ?>" target="_blank" rel="noopener">Plain-text version<span class="visually-hidden"> (opens in a new tab)</span></a></p>
    <div class="campaign-preview" data-preview-widths>
        <div class="admin-filters" role="group" aria-label="Preview width">
            <button type="button" class="admin-filter is-active" data-width="640" aria-pressed="true">Computer</button>
            <button type="button" class="admin-filter" data-width="380" aria-pressed="false">Phone</button>
        </div>
        <iframe class="campaign-preview__frame" src="<?= e($page . '?raw=1') ?>" title="Welcome email preview" style="width:640px" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>
    </div>

<?php if (can('subscribers.edit')): ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Send a test</h2>
        <form method="post" class="campaign-test">
            <?= csrf_field() ?><input type="hidden" name="do" value="test">
            <div class="admin-field">
                <label for="test-to">Send a test copy to</label>
                <input id="test-to" name="test_to" type="email" required value="<?= e($actor['Email']) ?>">
            </div>
            <button class="btn btn--outline btn--small" type="submit">Send test</button>
        </form>
    </section>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
