<?php
/**
 * Preview a campaign exactly as subscribers will see it, at desktop and
 * phone width, plus the plain-text version.
 *
 *   ?id=…          the preview page
 *   ?id=…&raw=1    just the email (shown inside the preview page)
 *   ?id=…&text=1   the plain-text version
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

admin_guard('subscribers.view');

$id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$campaign = $id > 0 ? db_row('SELECT * FROM NewsletterCampaigns WHERE CampaignID = ?', [$id]) : null;
if ($campaign === null) {
    admin_stop(404, 'Campaign not found', 'That campaign does not exist.');
}

// A sample subscriber, so the greeting shows how {first_name} looks.
$sample  = ['Name' => (string) current_admin()['Name'], 'UnsubscribeToken' => ''];
$message = campaign_render($campaign, $sample);

if (isset($_GET['raw'])) {
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: script-src 'none'");
    echo $message['html'];
    exit;
}
if (isset($_GET['text'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Subject: ' . $message['subject'] . "\n\n" . $message['text'];
    exit;
}

$raw       = url('admin/campaign-preview.php?id=' . $id . '&raw=1');
$pageTitle = 'Preview: ' . $campaign['CampaignName'];
$layout    = 'admin';
$activeNav = 'admin/campaigns.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e(url('admin/campaign-edit.php?id=' . $id)) ?>">Back to the campaign</a>
        <a class="btn btn--outline btn--small" href="<?= e(url('admin/campaign-preview.php?id=' . $id . '&text=1')) ?>" target="_blank" rel="noopener">Plain-text version</a>
    </div>
    <dl class="admin-fields campaign-meta">
        <div><dt>Subject</dt><dd><?= e($message['subject']) ?></dd></div>
<?php if (trim((string) $campaign['PreviewText']) !== ''): ?>
        <div><dt>Inbox preview</dt><dd><?= e($campaign['PreviewText']) ?></dd></div>
<?php endif; ?>
    </dl>
    <p class="admin-help">Shown with your first name in place of {first_name}. Pictures use the website's address, so they appear once the website is live.</p>

    <div class="campaign-preview" data-preview-widths>
        <div class="admin-filters" role="group" aria-label="Preview width">
            <button type="button" class="admin-filter is-active" data-width="640" aria-pressed="true">Computer</button>
            <button type="button" class="admin-filter" data-width="380" aria-pressed="false">Phone</button>
        </div>
        <iframe class="campaign-preview__frame" src="<?= e($raw) ?>" title="Email preview" style="width:640px" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>
    </div>
</main>
<script>
document.querySelectorAll('[data-preview-widths]').forEach((box) => {
    const frame = box.querySelector('iframe');
    box.querySelectorAll('[data-width]').forEach((button) => button.addEventListener('click', () => {
        frame.style.width = button.dataset.width + 'px';
        box.querySelectorAll('[data-width]').forEach((b) => {
            b.classList.toggle('is-active', b === button);
            b.setAttribute('aria-pressed', String(b === button));
        });
    }));
    frame.addEventListener('load', () => {
        try { frame.style.height = (frame.contentDocument.documentElement.scrollHeight + 20) + 'px'; } catch (e) { frame.style.height = '1600px'; }
    });
});
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
