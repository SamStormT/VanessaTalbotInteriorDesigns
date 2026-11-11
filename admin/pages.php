<?php
/**
 * Pages: every public page, its blocks in the order they appear, and where
 * each block is edited. Built from includes/page-map.php, the same map that
 * places the Edit buttons on the website.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/page-map.php';

admin_guard('dashboard.view');

$admin = current_admin();

$pageTitle = 'Pages';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <p class="admin-help">Every page of the website, with its blocks in the order they appear, and where to change each one. Tip: while you are signed in, every block on the website also shows <strong>Edit</strong> buttons that open the right screen.</p>

    <nav class="admin-filters" aria-label="Jump to a page">
<?php foreach (page_map() as $key => $page): ?>
        <a class="admin-filter" href="#page-<?= e($key) ?>"><?= e($page['title']) ?></a>
<?php endforeach; ?>
    </nav>

<?php foreach (page_map() as $key => $page): ?>
    <section class="admin-panel page-map" id="page-<?= e($key) ?>" aria-labelledby="page-<?= e($key) ?>-title">
        <div class="page-map__head">
            <h2 class="admin-panel__title" id="page-<?= e($key) ?>-title"><?= e($page['title']) ?></h2>
<?php if ($page['path'] !== null): ?>
            <a class="btn btn--outline btn--small" href="<?= e(url($page['path'])) ?>" target="_blank" rel="noopener">View page<span class="visually-hidden"> <?= e($page['title']) ?> (opens in a new tab)</span></a>
<?php endif; ?>
        </div>
        <ol class="page-map__blocks">
<?php foreach ($page['blocks'] as [$label, $targets]): ?>
            <li>
                <span class="page-map__label"><?= e($label) ?></span>
                <span class="page-map__links">
<?php foreach ($targets as $target):
    $link = page_map_link($target);
    if ($link === null) {
        continue;
    }
?>
<?php if (role_can($admin['Role'], $link['permission'])): ?>
                    <a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
<?php else: ?>
                    <span class="admin-muted" title="Your role can't change this"><?= e($link['label']) ?></span>
<?php endif; ?>
<?php endforeach; ?>
                </span>
            </li>
<?php endforeach; ?>
        </ol>
    </section>
<?php endforeach; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
