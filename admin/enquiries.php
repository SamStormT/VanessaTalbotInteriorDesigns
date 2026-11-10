<?php
/**
 * Contact Messages: every message sent through the contact form, newest
 * first, filtered by status.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/enquiries.php';

admin_guard('messages.view');

const ENQUIRIES_PAGE = 50;

$statuses = enquiry_statuses();
$types    = enquiry_types();
$filter   = is_string($_GET['status'] ?? null) && isset($statuses[$_GET['status']]) ? $_GET['status'] : '';
$page     = max(1, (int) ($_GET['page'] ?? 1));

$counts = array_column(db_all('SELECT Status, COUNT(*) AS Total FROM ContactMessages GROUP BY Status'), 'Total', 'Status');
$total  = $filter === '' ? array_sum($counts) : (int) ($counts[$filter] ?? 0);
$pages  = max(1, (int) ceil($total / ENQUIRIES_PAGE));

$where  = $filter === '' ? '' : 'WHERE Status = ?';
$rows   = db_all(
    "SELECT MessageID, Name, Surname, Email, Subject, EnquiryType, Status, CreatedDate
     FROM ContactMessages $where ORDER BY CreatedDate DESC, MessageID DESC
     LIMIT " . ENQUIRIES_PAGE . ' OFFSET ' . (($page - 1) * ENQUIRIES_PAGE),
    $filter === '' ? [] : [$filter]
);

$filterUrl = static fn(string $status, int $number = 1): string => url('admin/enquiries.php?' . http_build_query(array_filter(['status' => $status, 'page' => $number > 1 ? $number : null])));

$pageTitle = 'Contact Messages';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>

    <nav class="admin-filters" aria-label="Filter by status">
        <a class="admin-filter<?= $filter === '' ? ' is-active' : '' ?>" href="<?= e($filterUrl('')) ?>"<?= $filter === '' ? ' aria-current="page"' : '' ?>>All <span class="admin-filter__count"><?= array_sum($counts) ?></span></a>
<?php foreach ($statuses as $value => $label): ?>
        <a class="admin-filter<?= $filter === $value ? ' is-active' : '' ?>" href="<?= e($filterUrl($value)) ?>"<?= $filter === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="admin-filter__count"><?= (int) ($counts[$value] ?? 0) ?></span></a>
<?php endforeach; ?>
    </nav>

<?php if ($rows === []): ?>
    <?= admin_empty_state($filter === '' ? 'No messages yet' : 'No messages here', $filter === '' ? 'Messages sent through the contact form will appear here, and are also emailed to you.' : 'No messages have this status.', 'mail') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Received</th>
                    <th scope="col">From</th>
                    <th scope="col">About</th>
                    <th scope="col">Subject</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): $name = trim($row['Name'] . ' ' . ($row['Surname'] ?? '')); ?>
                <tr<?= $row['Status'] === 'New' ? ' class="is-unread"' : '' ?>>
                    <td><?= e(cms_format_date($row['CreatedDate'])) ?></td>
                    <td><?= e($name) ?><br><span class="admin-muted"><?= e($row['Email']) ?></span></td>
                    <td><?= e($types[$row['EnquiryType']] ?? $row['EnquiryType']) ?></td>
                    <td><?= $row['Subject'] ? e(mb_strimwidth($row['Subject'], 0, 60, '…')) : '<span class="admin-muted">None</span>' ?></td>
                    <td><span class="admin-badge admin-badge--<?= e(strtolower($row['Status'])) ?>"><?= e($statuses[$row['Status']]) ?></span></td>
                    <td class="admin-table__actions"><a class="btn btn--outline btn--small" href="<?= e(url('admin/enquiry.php?id=' . (int) $row['MessageID'])) ?>">Open<span class="visually-hidden"> message from <?= e($name) ?></span></a></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php if ($pages > 1): ?>
    <nav class="admin-actions" aria-label="Pages">
<?php if ($page > 1): ?>
        <a class="btn btn--outline btn--small" href="<?= e($filterUrl($filter, $page - 1)) ?>">Previous</a>
<?php endif; ?>
        <span class="admin-meta">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="btn btn--outline btn--small" href="<?= e($filterUrl($filter, $page + 1)) ?>">Next</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
