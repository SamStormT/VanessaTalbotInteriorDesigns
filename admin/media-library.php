<?php
/**
 * Media Library: search, preview and manage every uploaded image.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

admin_guard('media.view');

const MEDIA_LIBRARY_PAGE = 48;

$query    = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$category = is_string($_GET['category'] ?? null) ? mb_substr($_GET['category'], 0, 100) : '';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$total    = media_count($query, $category);
$pages    = max(1, (int) ceil($total / MEDIA_LIBRARY_PAGE));
$items    = media_search($query, MEDIA_LIBRARY_PAGE, ($page - 1) * MEDIA_LIBRARY_PAGE, $category);

$categories = array_column(db_all('SELECT DISTINCT Category FROM MediaLibrary WHERE Category IS NOT NULL ORDER BY Category'), 'Category');

$pageLink = static fn(int $number): string => url('admin/media-library.php?' . http_build_query(['q' => $query, 'category' => $category, 'page' => $number]));

$pageTitle = 'Media Library';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>

<?php if (can('media.create')): ?>
    <div class="media-drop media-drop--wide" data-library-drop>
        <label>
            <span class="media-drop__text">Drag images here, or <span class="media-drop__link">browse</span></span>
            <span class="media-drop__hint">JPG, PNG, WebP or GIF, up to <?= (int) (MEDIA_MAX_BYTES / 1048576) ?> MB each</span>
            <input class="visually-hidden" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
        </label>
        <p class="media-field__status" role="status"></p>
    </div>
<?php endif; ?>

    <form class="media-search" method="get">
        <div class="admin-field">
            <label for="media-search">Search</label>
            <input id="media-search" name="q" type="search" value="<?= e($query) ?>">
        </div>
<?php if ($categories): ?>
        <div class="admin-field">
            <label for="media-category">Used for</label>
            <select id="media-category" name="category">
                <option value="">All images</option>
<?php foreach ($categories as $option): ?>
                <option value="<?= e($option) ?>"<?= $option === $category ? ' selected' : '' ?>><?= e($option) ?></option>
<?php endforeach; ?>
            </select>
        </div>
<?php endif; ?>
        <button class="btn btn--outline btn--small" type="submit">Search</button>
    </form>

    <p class="admin-meta"><?= $total ?> image<?= $total === 1 ? '' : 's' ?></p>

<?php if ($items === []): ?>
    <?= admin_empty_state($query === '' && $category === '' ? 'No images yet' : 'No images found', $query === '' && $category === '' ? 'Images you upload from any page are kept here, ready to reuse.' : 'Try a different search.', 'camera') ?>
<?php else: ?>
    <ul class="media-grid">
<?php foreach ($items as $item): ?>
        <li class="media-card">
            <a class="media-card__link" href="<?= e(url('admin/media-edit.php?id=' . (int) $item['MediaID'])) ?>">
                <span class="media-card__preview"><img src="<?= e(url($item['FilePath'])) ?>" alt="<?= e($item['AltText'] ?? '') ?>" loading="lazy"></span>
                <span class="media-card__name"><?= e($item['FileName']) ?></span>
<?php if (empty($item['AltText'])): ?>
                <span class="admin-badge">Needs description</span>
<?php endif; ?>
            </a>
        </li>
<?php endforeach; ?>
    </ul>
<?php if ($pages > 1): ?>
    <nav class="admin-actions" aria-label="Pages">
<?php if ($page > 1): ?>
        <a class="btn btn--outline btn--small" href="<?= e($pageLink($page - 1)) ?>">Previous</a>
<?php endif; ?>
        <span class="admin-meta">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="btn btn--outline btn--small" href="<?= e($pageLink($page + 1)) ?>">Next</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
