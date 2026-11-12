<?php
/**
 * Newsletter Campaigns: create, edit, preview, test and send one campaign.
 *
 *   Draft  → (Mark as ready) → Ready → (Send) → Sending → Sent → (Archive)
 *
 * Any edit to a Ready campaign returns it to Draft, so what is sent is always
 * what was last checked. Sending, Sent and Archived campaigns can't be edited.
 *
 * Permissions are the Subscribers permissions (campaigns use subscriber
 * data): Admins create, edit, test and send; only the SuperAdmin deletes.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$do       = $posted && is_string($_POST['do'] ?? null) ? $_POST['do'] : 'save';

admin_guard('subscribers.' . match (true) {
    $creating                                 => 'create',
    $do === 'delete'                          => 'delete',
    in_array($do, ['archive', 'restore'], true) => 'archive',
    $posted                                   => 'edit',
    default                                   => 'view',
});

$module = campaign_module();
$actor  = current_admin();
$list   = url('admin/campaigns.php');

if ($creating) {
    // New campaign, optionally filled in from a blog post.
    $fromPost = filter_input(INPUT_GET, 'from_post', FILTER_VALIDATE_INT) ?: 0;
    $row      = campaign_defaults();
    $post     = null;
    if ($fromPost > 0) {
        $post = db_row("SELECT * FROM BlogPosts WHERE BlogID = ? AND Status = 'Published'", [$fromPost]);
        if ($post === null) {
            admin_stop(404, 'Post not found', 'Only a published blog post can become a newsletter.');
        }
        $row = campaign_from_post($post);
    }
    $row['Status'] = 'Draft';
} else {
    $id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $row = db_row(
        "SELECT c.*, CONCAT(a.Name, ' ', a.Surname) AS CreatorName, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName,
                b.Title AS PostTitle, b.Slug AS PostSlug
         FROM NewsletterCampaigns c
         LEFT JOIN AdminUsers a ON a.AdminUserID = c.CreatedBy
         LEFT JOIN AdminUsers u ON u.AdminUserID = c.LastEditedBy
         LEFT JOIN BlogPosts b ON b.BlogID = c.BlogID
         WHERE c.CampaignID = ?",
        [$id]
    );
    if ($row === null) {
        admin_stop(404, 'Campaign not found', 'That campaign does not exist.');
    }
}

$editable = in_array($row['Status'], ['Draft', 'Ready'], true);
$self     = $creating ? '' : url('admin/campaign-edit.php?id=' . $id);
$errors   = [];

$back = static function (string $type, string $message) use (&$self): never {
    flash_set($type, $message);
    header('Location: ' . $self);
    exit;
};

if ($posted) {
    if ($creating || $do === 'save' || $do === 'ready') {
        if (!$creating && !$editable) {
            $back('error', 'This campaign has been sent, so it can no longer be changed.');
        }
        [$data, $errors] = cms_validate($module, $_POST, $creating ? [] : $row);
        if ($errors === []) {
            if ($creating) {
                $extra = ['CreatedBy' => (int) $actor['AdminUserID'], 'Status' => 'Draft'];
                if (!empty($row['BlogID'])) {
                    $extra['BlogID'] = (int) $row['BlogID'];
                }
                $id   = cms_create($module, $data + $extra);
                $self = url('admin/campaign-edit.php?id=' . $id);
                $back('success', 'Campaign saved as a draft. Preview it and send yourself a test before sending.');
            }
            $changed = cms_save($module, $id, $data, $row);
            $merged  = array_merge($row, $data);
            if ($do === 'ready') {
                $problems = campaign_problems($merged);
                if ($problems !== []) {
                    if ($changed !== [] && $row['Status'] === 'Ready') {
                        db_query("UPDATE NewsletterCampaigns SET Status = 'Draft' WHERE CampaignID = ?", [$id]);
                    }
                    $back('error', 'Saved, but not ready yet: ' . implode(' ', $problems));
                }
                db_query("UPDATE NewsletterCampaigns SET Status = 'Ready' WHERE CampaignID = ?", [$id]);
                if ($row['Status'] !== 'Ready') {
                    activity_log('ready', 'NewsletterCampaigns', $id);
                }
                $back('success', 'Saved and marked Ready to send.');
            }
            if ($changed !== [] && $row['Status'] === 'Ready') {
                db_query("UPDATE NewsletterCampaigns SET Status = 'Draft' WHERE CampaignID = ?", [$id]);
                $back('success', 'Saved. Because it changed, the campaign is back to Draft: preview it, then mark it Ready again.');
            }
            $back('success', $changed === [] ? 'No changes to save.' : 'Saved.');
        }
    } elseif ($do === 'draft' && $row['Status'] === 'Ready') {
        db_query("UPDATE NewsletterCampaigns SET Status = 'Draft' WHERE CampaignID = ?", [$id]);
        activity_log('unready', 'NewsletterCampaigns', $id);
        $back('success', 'Back to Draft.');
    } elseif ($do === 'test') {
        $to = strtolower(trim(is_string($_POST['test_to'] ?? null) ? $_POST['test_to'] : ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $back('error', 'Enter a valid email address for the test.');
        }
        $error = campaign_send_test($row, $to, $actor);
        $back($error === null ? 'success' : 'error', $error === null ? 'Test email sent to ' . $to . '. Check how it looks before sending.' : 'The test could not be sent: ' . $error);
    } elseif ($do === 'send') {
        if (!hash_equals('SEND', strtoupper(trim(is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '')))) {
            $back('error', 'Type SEND in the box to confirm.');
        }
        $problem = campaign_start($id);
        $back($problem === null ? 'success' : 'error', $problem ?? 'Sending has started. Keep this page open until it finishes.');
    } elseif ($do === 'retry') {
        $count = campaign_retry_failed($id);
        $back('success', $count > 0 ? 'Trying ' . $count . ' failed email' . ($count === 1 ? '' : 's') . ' again.' : 'Nothing to retry.');
    } elseif ($do === 'archive' && in_array($row['Status'], ['Draft', 'Ready', 'Sent'], true)) {
        db_query("UPDATE NewsletterCampaigns SET Status = 'Archived' WHERE CampaignID = ?", [$id]);
        activity_log('archive', 'NewsletterCampaigns', $id, $row['Status'] . ' to Archived');
        $back('success', 'Campaign archived.');
    } elseif ($do === 'restore' && $row['Status'] === 'Archived') {
        $to = $row['SentDate'] !== null ? 'Sent' : 'Draft';
        db_query('UPDATE NewsletterCampaigns SET Status = ? WHERE CampaignID = ?', [$to, $id]);
        activity_log('restore', 'NewsletterCampaigns', $id, 'Archived to ' . $to);
        $back('success', 'Campaign restored.');
    } elseif ($do === 'delete' && in_array($row['Status'], ['Draft', 'Ready'], true)) {
        cms_delete($module, $id);
        flash_set('success', 'Campaign deleted.');
        header('Location: ' . $list);
        exit;
    } else {
        $back('error', 'That can\'t be done to this campaign.');
    }
}

$audience = campaign_audience_count();
$progress = $creating ? null : campaign_progress($id);
$tests    = $creating ? [] : db_all(
    "SELECT t.SentTo, t.SentDate, t.Successful, t.ErrorMessage, CONCAT(u.Name, ' ', u.Surname) AS SenderName
     FROM CampaignTestSends t LEFT JOIN AdminUsers u ON u.AdminUserID = t.SentBy
     WHERE t.CampaignID = ? ORDER BY t.SentDate DESC, t.TestID DESC LIMIT 10",
    [$id]
);
$lastTest  = $tests[0] ?? null;
$testedNow = $lastTest && $lastTest['Successful'] && ($row['LastEditedDate'] === null || strtotime($lastTest['SentDate']) >= strtotime($row['LastEditedDate']));
$statuses  = campaign_statuses();

$pageTitle = $creating ? 'New campaign' : $row['CampaignName'];
$layout    = 'admin';
$activeNav = 'admin/campaigns.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($list) ?>">All campaigns</a>
<?php if (!$creating): ?>
        <a class="btn btn--outline btn--small" href="<?= e(url('admin/campaign-preview.php?id=' . $id)) ?>">Preview</a>
<?php endif; ?>
    </div>

<?php if ($creating): ?>
    <ol class="campaign-steps" aria-label="Steps">
        <li class="is-current">Write</li><li>Preview</li><li>Send a test</li><li>Mark ready</li><li>Send</li>
    </ol>
<?php if ($post): ?>
    <p class="admin-alert admin-alert--success">Filled in from the blog post <strong><?= e($post['Title']) ?></strong>. Add an introduction, check everything, then save.</p>
<?php else: ?>
    <p class="admin-help">Tip: most campaigns start from a blog post. Open a published post and choose <strong>Create campaign from this post</strong> to fill all of this in.</p>
<?php endif; ?>
<?php else: ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Campaign</h2>
        <dl class="admin-fields">
            <div><dt>Status</dt><dd><span class="admin-badge admin-badge--campaign-<?= e(strtolower($row['Status'])) ?>"><?= e($statuses[$row['Status']] ?? $row['Status']) ?></span></dd></div>
<?php if ($row['PostTitle']): ?>
            <div><dt>From blog post</dt><dd><a href="<?= e(url('admin/blog-edit.php?id=' . (int) $row['BlogID'])) ?>"><?= e($row['PostTitle']) ?></a></dd></div>
<?php endif; ?>
            <div><dt>Created by</dt><dd><?= e(trim((string) $row['CreatorName']) ?: 'A former user') ?>, <?= e(cms_format_date($row['CreatedDate'])) ?></dd></div>
            <div><dt>Last edited</dt><dd><?= cms_last_edited_cell($row) ?></dd></div>
<?php if ($row['SentDate']): ?>
            <div><dt>Sent</dt><dd><?= e(cms_format_date($row['SentDate'])) ?> to <?= (int) $row['RecipientCount'] ?> subscriber<?= (int) $row['RecipientCount'] === 1 ? '' : 's' ?></dd></div>
<?php elseif (in_array($row['Status'], ['Draft', 'Ready'], true)): ?>
            <div><dt>Would go to</dt><dd><?= $audience ?> confirmed subscriber<?= $audience === 1 ? '' : 's' ?> <span class="admin-muted">(unconfirmed and unsubscribed people are never emailed)</span></dd></div>
<?php endif; ?>
        </dl>
    </section>
<?php endif; ?>

<?php if (!$creating && in_array($row['Status'], ['Sending', 'Sent', 'Archived'], true) && $progress['total'] > 0): ?>
    <section class="admin-panel" data-campaign-progress="<?= e(url('admin/campaign-send.php')) ?>" data-campaign-id="<?= (int) $id ?>" data-sending="<?= $row['Status'] === 'Sending' ? '1' : '0' ?>">
        <h2 class="admin-panel__title"><?= $row['Status'] === 'Sending' ? 'Sending' : 'Delivery' ?></h2>
        <progress class="campaign-progress" max="<?= (int) $progress['total'] ?>" value="<?= (int) ($progress['sent'] + $progress['failed'] + $progress['skipped']) ?>"></progress>
        <p class="admin-meta" role="status" data-progress-text>
            <?= (int) $progress['sent'] ?> sent · <?= (int) $progress['failed'] ?> failed · <?= (int) $progress['skipped'] ?> skipped (unsubscribed while sending) · <?= (int) ($progress['pending'] + $progress['sending']) ?> waiting
        </p>
<?php if ($row['Status'] === 'Sending'): ?>
        <p class="admin-help" data-progress-help>Sending in batches of <?= CAMPAIGN_BATCH_SIZE ?>. Keep this page open until it finishes. If you close it, come back and press Resume.</p>
        <button type="button" class="btn btn--primary btn--small" data-progress-resume hidden>Resume sending</button>
<?php endif; ?>
<?php if ($progress['failed'] > 0 && $row['Status'] === 'Sent' && can('subscribers.edit')): ?>
        <form method="post" class="admin-inline-action" data-confirm="Try the failed emails again? People who have since unsubscribed are skipped.">
            <?= csrf_field() ?><input type="hidden" name="do" value="retry">
            <button class="btn btn--outline btn--small" type="submit">Retry failed emails</button>
        </form>
<?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($creating || $editable): ?>
    <?= str_replace('<div class="admin-actions"><button class="btn btn--primary" type="submit">Save changes</button>',
        // The hidden "save" comes first, so the Ready button's own value wins when it is clicked.
        '<input type="hidden" name="do" value="save"><div class="admin-actions"><button class="btn btn--primary" type="submit">' . ($creating ? 'Save draft' : 'Save changes') . '</button>'
        . ($creating ? '' : '<button class="btn btn--outline" type="submit" name="do" value="ready">Save and mark ready</button>'),
        cms_render_form($module, $row, $errors, $list, $posted)) ?>
<?php else: ?>
    <p class="admin-help">This campaign has been <?= $row['Status'] === 'Sending' ? 'started' : 'sent' ?>, so its content can't be changed. Use Preview to see exactly what was sent.</p>
<?php endif; ?>

<?php if (!$creating && $editable && can('subscribers.edit')): ?>
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
        <p class="admin-help">Tests use your first name in place of {first_name} and say [TEST] in the subject. They are not sent to subscribers.</p>
<?php if ($tests): ?>
        <h3 class="campaign-subtitle">Test history</h3>
        <ul class="campaign-tests">
<?php foreach ($tests as $test): ?>
            <li><?= $test['Successful'] ? '<span class="admin-badge admin-badge--subscribed">Sent</span>' : '<span class="admin-badge admin-badge--closed">Failed</span>' ?>
                <?= e(cms_format_date($test['SentDate'])) ?> to <?= e($test['SentTo']) ?><span class="admin-muted"> by <?= e(trim((string) $test['SenderName']) ?: 'a former user') ?></span>
                <?= $test['ErrorMessage'] ? '<br><span class="admin-error">' . e($test['ErrorMessage']) . '</span>' : '' ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </section>
<?php endif; ?>

<?php if (!$creating && $row['Status'] === 'Ready' && can('subscribers.edit')): ?>
    <section class="admin-panel campaign-send">
        <h2 class="admin-panel__title">Send to subscribers</h2>
<?php if (!$testedNow): ?>
        <p class="admin-alert admin-alert--error">You haven't sent yourself a test since the last change. It's worth checking the test email first.</p>
<?php endif; ?>
<?php if ($audience === 0): ?>
        <p>There are no confirmed subscribers yet, so there is no one to send to.</p>
<?php else: ?>
        <p>This will email <strong><?= $audience ?> confirmed subscriber<?= $audience === 1 ? '' : 's' ?></strong>. It can't be undone.</p>
        <form method="post" class="campaign-test">
            <?= csrf_field() ?><input type="hidden" name="do" value="send">
            <div class="admin-field">
                <label for="send-confirm">Type SEND to confirm</label>
                <input id="send-confirm" name="confirm" type="text" autocomplete="off" required>
            </div>
            <button class="btn btn--primary" type="submit">Send campaign</button>
        </form>
<?php endif; ?>
        <form method="post" class="admin-inline-action">
            <?= csrf_field() ?><input type="hidden" name="do" value="draft">
            <button class="admin-delete__button" type="submit">Not ready after all: back to Draft</button>
        </form>
    </section>
<?php endif; ?>

<?php if (!$creating): ?>
    <div class="admin-delete">
<?php if (can('subscribers.archive') && in_array($row['Status'], ['Draft', 'Ready', 'Sent'], true)): ?>
        <form method="post" class="admin-inline-action" data-confirm="Archive this campaign? It moves out of the main list; you can restore it.">
            <?= csrf_field() ?><input type="hidden" name="do" value="archive">
            <button class="admin-delete__button" type="submit">Archive</button>
        </form>
<?php elseif (can('subscribers.archive') && $row['Status'] === 'Archived'): ?>
        <form method="post" class="admin-inline-action">
            <?= csrf_field() ?><input type="hidden" name="do" value="restore">
            <button class="btn btn--outline btn--small" type="submit">Restore from archive</button>
        </form>
<?php endif; ?>
<?php if (can('subscribers.delete') && in_array($row['Status'], ['Draft', 'Ready'], true)): ?>
        <form method="post" class="admin-inline-action" data-confirm="Permanently delete this unsent campaign?">
            <?= csrf_field() ?><input type="hidden" name="do" value="delete">
            <button class="admin-delete__button" type="submit">Delete permanently</button>
        </form>
<?php endif; ?>
    </div>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
