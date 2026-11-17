<?php
/**
 * Marketing → Statistics: page views per day, the most-read pages, and which
 * websites visitors came from, over the last 7, 30 or 90 days. Counted
 * without cookies or personal details (see includes/statistics.php).
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/statistics.php';

admin_guard('page_sections.view');

$days  = in_array((int) ($_GET['days'] ?? 30), [7, 30, 90], true) ? (int) ($_GET['days'] ?? 30) : 30;
$start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$prev  = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));

$byDay = array_column(db_all('SELECT ViewDate, SUM(Views) AS Views FROM PageViews WHERE ViewDate >= ? GROUP BY ViewDate', [$start]), 'Views', 'ViewDate');
$total = array_sum($byDay);
$before = (int) db_value('SELECT COALESCE(SUM(Views), 0) FROM PageViews WHERE ViewDate >= ? AND ViewDate < ?', [$prev, $start]);
$pages  = db_all('SELECT Path, SUM(Views) AS Views FROM PageViews WHERE ViewDate >= ? GROUP BY Path ORDER BY Views DESC LIMIT 15', [$start]);
$sources = db_all('SELECT Source, SUM(Views) AS Views FROM PageReferrers WHERE ViewDate >= ? GROUP BY Source ORDER BY Views DESC LIMIT 15', [$start]);
$kinds = [];
foreach (db_all('SELECT Source, SUM(Views) AS Views FROM PageReferrers WHERE ViewDate >= ? GROUP BY Source', [$start]) as $row) {
    $kind = stats_source_kind((string) $row['Source']);
    $kinds[$kind] = ($kinds[$kind] ?? 0) + (int) $row['Views'];
}
arsort($kinds);

// Every day in the period, including days with no views.
$series = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $series[$date] = (int) ($byDay[$date] ?? 0);
}
$peak     = max(1, max($series));
$busiest  = array_search(max($series), $series, true);

/** A readable name for a counted page. */
$titles = [];
foreach (page_map() as $page) {
    if (!empty($page['path'])) {
        $titles[$page['path']] = $page['title'];
    }
}
$name = static function (string $path) use ($titles): string {
    if (preg_match('#^blog/blog-post\.php\?slug=([a-z0-9-]+)$#', $path, $m)) {
        return 'Article: ' . (db_value('SELECT Title FROM BlogPosts WHERE Slug = ?', [$m[1]]) ?: $m[1]);
    }
    if (preg_match('#^portfolio/project\.php\?slug=([a-z0-9-]+)$#', $path, $m)) {
        return 'Project: ' . (db_value('SELECT Title FROM PortfolioProjects WHERE Slug = ?', [$m[1]]) ?: $m[1]);
    }
    if (preg_match('#^blog\.php\?category=([a-z0-9-]+)$#', $path, $m)) {
        return 'Blog category: ' . (db_value('SELECT CategoryName FROM BlogCategories WHERE Slug = ?', [$m[1]]) ?: $m[1]);
    }
    if (preg_match('#^services/([a-z0-9-]+)\.php$#', $path, $m)) {
        return 'Service: ' . (db_value('SELECT ServiceName FROM Services WHERE Slug = ?', [$m[1]]) ?: $m[1]);
    }
    return $titles[$path] ?? $path;
};
$change = $before > 0 ? (int) round(($total - $before) / $before * 100) : null;

$pageTitle = 'Statistics';
$layout    = 'admin';
require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content statistics">
    <div class="admin-toolbar">
        <nav class="admin-filters" aria-label="Period">
<?php foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $value => $label): ?>
            <a class="admin-filter<?= $value === $days ? ' is-active' : '' ?>" href="<?= e(url('admin/statistics.php?days=' . $value)) ?>"<?= $value === $days ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
        </nav>
    </div>
    <p class="admin-help">Counted without cookies or personal details: each time a page is opened, except by signed-in staff and robots. Someone reading three pages counts as three views.</p>

    <section class="stats-tiles" aria-label="Summary">
        <div class="stats-tile">
            <p class="stats-tile__label">Page views</p>
            <p class="stats-tile__value"><?= number_format($total) ?></p>
            <p class="stats-tile__note"><?= $change === null ? 'No earlier period to compare yet' : ($change >= 0 ? 'Up ' : 'Down ') . abs($change) . '% on the ' . $days . ' days before' ?></p>
        </div>
        <div class="stats-tile">
            <p class="stats-tile__label">Busiest day</p>
            <p class="stats-tile__value"><?= $total > 0 ? e(date('j M', strtotime((string) $busiest))) : '–' ?></p>
            <p class="stats-tile__note"><?= $total > 0 ? number_format((int) max($series)) . ' views' : 'No views yet' ?></p>
        </div>
        <div class="stats-tile">
            <p class="stats-tile__label">Came from other websites</p>
            <p class="stats-tile__value"><?= number_format(array_sum($kinds)) ?></p>
            <p class="stats-tile__note"><?= $kinds ? e(implode(', ', array_map(static fn($kind, $views) => $kind . ' ' . $views, array_keys($kinds), $kinds))) : 'None yet' ?></p>
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="chart-title">
        <h2 class="admin-panel__title" id="chart-title">Page views per day</h2>
        <div class="stats-chart" aria-hidden="true">
            <div class="stats-chart__scale"><span><?= number_format($peak) ?></span><span><?= $peak % 2 === 0 ? number_format($peak / 2) : number_format($peak / 2, 1) ?></span><span>0</span></div>
            <div class="stats-chart__plot stats-chart__plot--<?= $days ?>">
<?php foreach ($series as $date => $views): ?>
                <span class="stats-bar" style="--h: <?= round($views / $peak * 100, 1) ?>%" data-tip="<?= e(date('D j M', strtotime($date)) . ': ' . number_format($views) . ' view' . ($views === 1 ? '' : 's')) ?>"></span>
<?php endforeach; ?>
            </div>
            <div class="stats-chart__dates"><span><?= e(date('j M', strtotime($start))) ?></span><span>Today</span></div>
        </div>
        <details class="stats-table">
            <summary>Show as a table</summary>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead><tr><th scope="col">Day</th><th scope="col">Page views</th></tr></thead>
                    <tbody>
<?php foreach (array_reverse($series, true) as $date => $views): ?>
                        <tr><td><?= e(date('D j M Y', strtotime($date))) ?></td><td><?= number_format($views) ?></td></tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    <div class="stats-columns">
        <section class="admin-panel" aria-labelledby="pages-title">
            <h2 class="admin-panel__title" id="pages-title">Most read</h2>
<?php if ($pages === []): ?>
            <p class="admin-muted">No views yet.</p>
<?php else: ?>
            <table class="admin-table">
                <thead><tr><th scope="col">Page</th><th scope="col">Views</th></tr></thead>
                <tbody>
<?php foreach ($pages as $row): ?>
                    <tr><td><?= e($name((string) $row['Path'])) ?></td><td><?= number_format((int) $row['Views']) ?></td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
<?php endif; ?>
        </section>

        <section class="admin-panel" aria-labelledby="sources-title">
            <h2 class="admin-panel__title" id="sources-title">Where visitors came from</h2>
<?php if ($sources === []): ?>
            <p class="admin-muted">None yet. Visits from a typed address, a bookmark, an email or an app don't show here.</p>
<?php else: ?>
            <table class="admin-table">
                <thead><tr><th scope="col">Website</th><th scope="col">Kind</th><th scope="col">Visits</th></tr></thead>
                <tbody>
<?php foreach ($sources as $row): ?>
                    <tr><td><?= e($row['Source']) ?></td><td><?= e(stats_source_kind((string) $row['Source'])) ?></td><td><?= number_format((int) $row['Views']) ?></td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
            <p class="admin-help">Visits from a typed address, a bookmark, an email or an app aren't listed.</p>
<?php endif; ?>
        </section>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
