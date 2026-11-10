<?php
/**
 * Subscribers: everyone who signed up to the newsletter, with whether they
 * confirmed by email. Admins can unsubscribe someone who asks; the SuperAdmin
 * can erase a record completely (a POPIA deletion request).
 *
 * Sending newsletters is a later phase.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

$posted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$do     = $posted && is_string($_POST['do'] ?? null) ? $_POST['do'] : '';

admin_guard(match ($do) { 'unsubscribe' => 'subscribers.archive', 'erase' => 'subscribers.delete', default => 'subscribers.view' });

const SUBSCRIBERS_PAGE = 50;

$filters = [
    ''             => ['All', '1'],
    'confirmed'    => ['Confirmed', "SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NOT NULL"],
    'pending'      => ['Waiting to confirm', "SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NULL"],
    'unsubscribed' => ['Unsubscribed', "SubscriptionStatus = 'Unsubscribed'"],
];
$filter = is_string($_GET['show'] ?? null) && isset($filters[$_GET['show']]) ? $_GET['show'] : '';
$query  = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$page   = max(1, (int) ($_GET['page'] ?? 1));
$filterUrl = static fn(array $change = []): string => url('admin/subscribers.php?' . http_build_query(array_filter(array_merge(['show' => $filter, 'q' => $query], $change))));

if ($do !== '') {
    $id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $row = db_row('SELECT SubscriberID, Email, SubscriptionStatus FROM Subscribers WHERE SubscriberID = ?', [$id]);

    if ($row === null) {
        flash_set('error', 'That subscriber no longer exists.');
    } elseif ($do === 'unsubscribe') {
        if ($row['SubscriptionStatus'] === 'Subscribed') {
            db()->beginTransaction();
            db_query("UPDATE Subscribers SET SubscriptionStatus = 'Unsubscribed', UnsubscribedDate = NOW() WHERE SubscriberID = ?", [$id]);
            activity_log('unsubscribe', 'Subscribers', $id);
            db()->commit();
        }
        flash_set('success', $row['Email'] . ' is unsubscribed.');
    } elseif ($do === 'erase') {
        db()->beginTransaction();
        db_query('DELETE FROM Subscribers WHERE SubscriberID = ?', [$id]);
        activity_log('delete', 'Subscribers', $id);
        db()->commit();
        flash_set('success', 'The subscriber record was erased.');
    }
    header('Location: ' . $filterUrl());
    exit;
}

$where  = $filters[$filter][1];
$params = [];
if ($query !== '') {
    $where   .= ' AND (Email LIKE ? OR Name LIKE ? OR Surname LIKE ?)';
    $like     = '%' . addcslashes($query, '%_\\') . '%';
    $params   = [$like, $like, $like];
}

$total = (int) db_value("SELECT COUNT(*) FROM Subscribers WHERE $where", $params);
$pages = max(1, (int) ceil($total / SUBSCRIBERS_PAGE));
$rows  = db_all(
    "SELECT SubscriberID, Name, Surname, Email, SubscriptionStatus, Source, SubscribedDate, ConfirmedDate, UnsubscribedDate
     FROM Subscribers WHERE $where ORDER BY SubscribedDate DESC, SubscriberID DESC
     LIMIT " . SUBSCRIBERS_PAGE . ' OFFSET ' . (($page - 1) * SUBSCRIBERS_PAGE),
    $params
);

$counts = db_row(
    "SELECT COUNT(*) AS total,
            SUM(SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NOT NULL) AS confirmed,
            SUM(SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NULL) AS pending,
            SUM(SubscriptionStatus = 'Unsubscribed') AS unsubscribed
     FROM Subscribers"
);
$counts[''] = $counts['total'];

$pageTitle = 'Subscribers';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <p class="admin-help">People join from the home page or by ticking the box on the contact form. Each one is emailed a link to confirm, and only confirmed subscribers will receive newsletters.</p>

    <nav class="admin-filters" aria-label="Filter subscribers">
<?php foreach ($filters as $key => [$label]): ?>
        <a class="admin-filter<?= $filter === $key ? ' is-active' : '' ?>" href="<?= e($filterUrl(['show' => $key, 'page' => null])) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="admin-filter__count"><?= (int) $counts[$key] ?></span></a>
<?php endforeach; ?>
    </nav>

    <form class="media-search" method="get">
<?php if ($filter !== ''): ?>
        <input type="hidden" name="show" value="<?= e($filter) ?>">
<?php endif; ?>
        <div class="admin-field">
            <label for="subscriber-search">Search by name or email</label>
            <input id="subscriber-search" name="q" type="search" value="<?= e($query) ?>">
        </div>
        <button class="btn btn--outline btn--small" type="submit">Search</button>
    </form>

<?php if ($rows === []): ?>
    <?= admin_empty_state($query === '' && $filter === '' ? 'No subscribers yet' : 'No subscribers found', $query === '' && $filter === '' ? 'Newsletter sign-ups will appear here.' : 'Try a different search or filter.', 'mail') ?>
<?php else: ?>
    <p class="admin-meta"><?= $total ?> subscriber<?= $total === 1 ? '' : 's' ?></p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Email</th>
                    <th scope="col">Name</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined from</th>
                    <th scope="col">Signed up</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row):
    [$badge, $state] = match (true) {
        $row['SubscriptionStatus'] === 'Unsubscribed' => ['unsubscribed', 'Unsubscribed ' . date('j M Y', strtotime((string) $row['UnsubscribedDate']))],
        $row['ConfirmedDate'] === null                => ['pending', 'Waiting to confirm'],
        default                                       => ['subscribed', 'Confirmed ' . date('j M Y', strtotime($row['ConfirmedDate']))],
    };
?>
                <tr>
                    <td><?= e($row['Email']) ?></td>
                    <td><?= e(trim(($row['Name'] ?? '') . ' ' . ($row['Surname'] ?? ''))) ?: '<span class="admin-muted">Not given</span>' ?></td>
                    <td><span class="admin-badge admin-badge--<?= $badge ?>"><?= e($state) ?></span></td>
                    <td><?= e($row['Source'] === 'Homepage Section' ? 'Home page' : $row['Source']) ?></td>
                    <td><?= e(date('j M Y', strtotime($row['SubscribedDate']))) ?></td>
                    <td class="admin-table__actions">
<?php if ($row['SubscriptionStatus'] === 'Subscribed' && can('subscribers.archive')): ?>
                        <form method="post" data-confirm="Unsubscribe <?= e($row['Email']) ?>? They will not receive newsletters unless they sign up again.">
                            <?= csrf_field() ?><input type="hidden" name="do" value="unsubscribe"><input type="hidden" name="id" value="<?= (int) $row['SubscriberID'] ?>">
                            <button class="btn btn--outline btn--small" type="submit">Unsubscribe<span class="visually-hidden"> <?= e($row['Email']) ?></span></button>
                        </form>
<?php endif; ?>
<?php if (can('subscribers.delete')): ?>
                        <form method="post" data-confirm="Permanently erase every record of <?= e($row['Email']) ?>? Use this when someone asks for their personal information to be deleted. This cannot be undone.">
                            <?= csrf_field() ?><input type="hidden" name="do" value="erase"><input type="hidden" name="id" value="<?= (int) $row['SubscriberID'] ?>">
                            <button class="admin-delete__button" type="submit">Erase<span class="visually-hidden"> <?= e($row['Email']) ?></span></button>
                        </form>
<?php endif; ?>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php if ($pages > 1): ?>
    <nav class="admin-actions" aria-label="Pages">
<?php if ($page > 1): ?>
        <a class="btn btn--outline btn--small" href="<?= e($filterUrl(['page' => $page - 1])) ?>">Previous</a>
<?php endif; ?>
        <span class="admin-meta">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="btn btn--outline btn--small" href="<?= e($filterUrl(['page' => $page + 1])) ?>">Next</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
