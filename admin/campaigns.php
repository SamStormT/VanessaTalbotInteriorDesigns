<?php
/**
 * Newsletter Campaigns: every campaign, newest first, by status.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

admin_guard('subscribers.view');

$statuses = campaign_statuses();
$filter   = is_string($_GET['status'] ?? null) && isset($statuses[$_GET['status']]) ? $_GET['status'] : '';

$rows = db_all(
    "SELECT c.CampaignID, c.CampaignName, c.Subject, c.Status, c.SentDate, c.RecipientCount, c.CreatedDate, c.LastEditedDate,
            b.Title AS PostTitle,
            (SELECT MAX(t.SentDate) FROM CampaignTestSends t WHERE t.CampaignID = c.CampaignID AND t.Successful = 1) AS LastTest
     FROM NewsletterCampaigns c LEFT JOIN BlogPosts b ON b.BlogID = c.BlogID
     WHERE " . ($filter === '' ? "c.Status <> 'Archived'" : 'c.Status = ?') . '
     ORDER BY FIELD(c.Status, \'Sending\', \'Ready\', \'Draft\', \'Sent\', \'Archived\'), COALESCE(c.SentDate, c.LastEditedDate, c.CreatedDate) DESC',
    $filter === '' ? [] : [$filter]
);
$counts   = array_column(db_all('SELECT Status, COUNT(*) AS Total FROM NewsletterCampaigns GROUP BY Status'), 'Total', 'Status');
$audience = campaign_audience_count();

$pageTitle = 'Newsletter Campaigns';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <nav class="admin-filters" aria-label="Filter by status">
            <a class="admin-filter<?= $filter === '' ? ' is-active' : '' ?>" href="<?= e(url('admin/campaigns.php')) ?>"<?= $filter === '' ? ' aria-current="page"' : '' ?>>Current <span class="admin-filter__count"><?= array_sum($counts) - (int) ($counts['Archived'] ?? 0) ?></span></a>
<?php foreach ($statuses as $value => $label): ?>
            <a class="admin-filter<?= $filter === $value ? ' is-active' : '' ?>" href="<?= e(url('admin/campaigns.php?status=' . $value)) ?>"<?= $filter === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="admin-filter__count"><?= (int) ($counts[$value] ?? 0) ?></span></a>
<?php endforeach; ?>
        </nav>
<?php if (can('subscribers.create')): ?>
        <a class="btn btn--primary btn--small" href="<?= e(url('admin/campaign-edit.php')) ?>">New campaign</a>
<?php endif; ?>
    </div>

    <p class="admin-help"><?= $audience ?> confirmed subscriber<?= $audience === 1 ? '' : 's' ?> would receive a campaign sent now. To turn a blog post into a newsletter, open the post and choose <strong>Create campaign from this post</strong>.</p>

<?php if ($rows === []): ?>
    <?= admin_empty_state($filter === '' ? 'No campaigns yet' : 'No campaigns here', $filter === '' ? 'Create one from a published blog post, or start a new campaign.' : 'No campaigns have this status.', 'mail') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Campaign</th>
                    <th scope="col">Status</th>
                    <th scope="col">Sent</th>
                    <th scope="col">Last test</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['CampaignName']) ?><br><span class="admin-muted"><?= e($row['Subject']) ?><?= $row['PostTitle'] ? ' · from "' . e($row['PostTitle']) . '"' : '' ?></span></td>
                    <td><span class="admin-badge admin-badge--campaign-<?= e(strtolower($row['Status'])) ?>"><?= e($statuses[$row['Status']] ?? $row['Status']) ?></span></td>
                    <td><?= $row['SentDate'] ? e(date('j M Y, H:i', strtotime($row['SentDate']))) . '<br><span class="admin-muted">' . (int) $row['RecipientCount'] . ' recipient' . ((int) $row['RecipientCount'] === 1 ? '' : 's') . '</span>' : '<span class="admin-muted">Not yet</span>' ?></td>
                    <td><?= $row['LastTest'] ? e(date('j M Y, H:i', strtotime($row['LastTest']))) : '<span class="admin-muted">None</span>' ?></td>
                    <td class="admin-table__actions"><a class="btn btn--outline btn--small" href="<?= e(url('admin/campaign-edit.php?id=' . (int) $row['CampaignID'])) ?>">Open<span class="visually-hidden"> <?= e($row['CampaignName']) ?></span></a></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
