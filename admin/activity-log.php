<?php
/**
 * System → Activity log (SuperAdmin): everything done in the admin area and
 * by the website (sign-ins, edits, publishing, sends, clean-ups, visitor
 * sign-ups and messages), newest first, with filters. Useful to spot
 * unexpected sign-ins or changes.
 *
 * Also shows when old personal details were last cleaned up, with a button
 * to run the clean-up now (see includes/retention.php).
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/retention.php';

admin_guard('activity.view');

const ACTIVITY_PAGE_SIZE = 50;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_valid() || ($_POST['do'] ?? '') !== 'cleanup' || !can('settings.edit')) {
        admin_stop(400, 'Not done', 'This page expired. Please try again.');
    }
    $removed = data_cleanup(true);
    flash_set('success', $removed ? 'Clean-up done: removed ' . implode(', ', array_map(static fn($label, $count) => $count . ' ' . $label, array_keys($removed), $removed)) . '.' : 'Clean-up done: nothing was past its keeping period.');
    header('Location: ' . url('admin/activity-log.php'));
    exit;
}

$who    = filter_input(INPUT_GET, 'who', FILTER_VALIDATE_INT) ?: 0;
$action = is_string($_GET['action'] ?? null) ? preg_replace('/[^a-z_]/', '', $_GET['action']) : '';
$from   = is_string($_GET['from'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : '';
$to     = is_string($_GET['to'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']) ? $_GET['to'] : '';
$words  = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$page   = max(1, (int) ($_GET['page'] ?? 1));

$where  = ['1'];
$params = [];
if ($who > 0) {
    $where[]  = 'l.AdminUserID = ?';
    $params[] = $who;
} elseif ($who === -1) {
    $where[] = 'l.AdminUserID IS NULL';
}
if ($action !== '') {
    $where[]  = 'l.Action = ?';
    $params[] = $action;
}
if ($from !== '') {
    $where[]  = 'l.CreatedDate >= ?';
    $params[] = $from . ' 00:00:00';
}
if ($to !== '') {
    $where[]  = 'l.CreatedDate <= ?';
    $params[] = $to . ' 23:59:59';
}
if ($words !== '') {
    $where[]  = '(l.Details LIKE ? OR l.EntityType LIKE ?)';
    $like     = '%' . addcslashes($words, '%_\\') . '%';
    array_push($params, $like, $like);
}
$sqlWhere = implode(' AND ', $where);

$total = (int) db_value("SELECT COUNT(*) FROM ActivityLog l WHERE $sqlWhere", $params);
$pages = max(1, (int) ceil($total / ACTIVITY_PAGE_SIZE));
$rows  = db_all(
    "SELECT l.*, CONCAT(u.Name, ' ', u.Surname) AS UserName FROM ActivityLog l LEFT JOIN AdminUsers u ON u.AdminUserID = l.AdminUserID
     WHERE $sqlWhere ORDER BY l.LogID DESC LIMIT " . ACTIVITY_PAGE_SIZE . ' OFFSET ' . (($page - 1) * ACTIVITY_PAGE_SIZE),
    $params
);
$people  = db_all("SELECT AdminUserID, CONCAT(Name, ' ', Surname) AS UserName FROM AdminUsers ORDER BY Name, Surname");
$actions = array_column(db_all('SELECT DISTINCT Action FROM ActivityLog ORDER BY Action'), 'Action');
$last    = db_value('SELECT LastCleanupDate FROM WebsiteSettings WHERE SettingID = 1');
$failed  = (int) db_value('SELECT COUNT(*) FROM LoginAttempts WHERE Successful = 0 AND AttemptDate >= NOW() - INTERVAL 1 DAY');

$label   = static fn(string $action): string => ucfirst(str_replace('_', ' ', $action));
$listUrl = static fn(array $change = []): string => url('admin/activity-log.php') . '?' . http_build_query(array_filter(
    array_merge(['who' => $who ?: null, 'action' => $action, 'from' => $from, 'to' => $to, 'q' => $words], $change),
    static fn($v) => $v !== null && $v !== ''
));

$pageTitle = 'Activity log';
$layout    = 'admin';
require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>

    <section class="admin-panel" aria-labelledby="privacy-title">
        <h2 class="admin-panel__title" id="privacy-title">Security and privacy at a glance</h2>
        <ul class="health-list">
            <li><strong><?= $failed ?></strong> failed sign-in attempt<?= $failed === 1 ? '' : 's' ?> in the last 24 hours. <span class="admin-muted">Five wrong passwords lock an account for <?= LOGIN_WINDOW_MINUTES ?> minutes.</span></li>
            <li>Old personal details last cleaned up: <strong><?= $last ? e(cms_format_date((string) $last)) : 'not yet' ?></strong>. <span class="admin-muted">Runs once a day; how long things are kept is set in Website Settings → Privacy.</span></li>
        </ul>
<?php if (can('settings.edit')): ?>
        <form method="post" class="admin-inline-action">
            <?= csrf_field() ?><input type="hidden" name="do" value="cleanup">
            <button class="btn btn--outline btn--small" type="submit">Clean up now</button>
        </form>
<?php endif; ?>
    </section>

    <form class="media-search activity-filters" method="get">
        <div class="admin-field">
            <label for="who">Who</label>
            <select id="who" name="who">
                <option value="">Everyone</option>
                <option value="-1"<?= $who === -1 ? ' selected' : '' ?>>The website (visitors, automatic tasks)</option>
<?php foreach ($people as $person): ?>
                <option value="<?= (int) $person['AdminUserID'] ?>"<?= (int) $person['AdminUserID'] === $who ? ' selected' : '' ?>><?= e($person['UserName']) ?></option>
<?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field">
            <label for="action">What</label>
            <select id="action" name="action">
                <option value="">Anything</option>
<?php foreach ($actions as $option): ?>
                <option value="<?= e($option) ?>"<?= $option === $action ? ' selected' : '' ?>><?= e($label($option)) ?></option>
<?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field"><label for="from">From</label><input id="from" name="from" type="date" value="<?= e($from) ?>"></div>
        <div class="admin-field"><label for="to">To</label><input id="to" name="to" type="date" value="<?= e($to) ?>"></div>
        <div class="admin-field"><label for="q">Words</label><input id="q" name="q" type="search" value="<?= e($words) ?>"></div>
        <button class="btn btn--outline btn--small" type="submit">Show</button>
    </form>

    <p class="admin-meta"><?= $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?></p>
<?php if ($rows === []): ?>
    <?= admin_empty_state('Nothing found', 'Try different filters.', 'eye') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">What</th><th scope="col">Item</th><th scope="col">Details</th><th scope="col">Internet address</th></tr></thead>
            <tbody>
<?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e(cms_format_date($row['CreatedDate'])) ?></td>
                    <td><?= $row['AdminUserID'] === null ? '<span class="admin-muted">Website</span>' : e(trim((string) $row['UserName']) ?: 'A former user') ?></td>
                    <td><?= e($label((string) $row['Action'])) ?></td>
                    <td><?= $row['EntityType'] ? e($row['EntityType'] . ($row['EntityID'] ? ' #' . $row['EntityID'] : '')) : '' ?></td>
                    <td><?= e((string) $row['Details']) ?></td>
                    <td><?= e((string) $row['IPAddress']) ?></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php if ($pages > 1): ?>
    <nav class="admin-actions" aria-label="Pages">
<?php if ($page > 1): ?>
        <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page - 1])) ?>">Newer</a>
<?php endif; ?>
        <span class="admin-meta">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page + 1])) ?>">Older</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
