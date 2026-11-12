<?php
/**
 * Blog Posts: every post, filtered by status or category, with search.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/blog.php';

admin_guard('blogs.view');

const BLOG_ADMIN_PAGE = 30;

$statuses = ['Draft' => 'Draft', 'Published' => 'Published', 'Archived' => 'Archived'];
$status   = is_string($_GET['status'] ?? null) && isset($statuses[$_GET['status']]) ? $_GET['status'] : '';
$category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$query    = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$page     = max(1, (int) ($_GET['page'] ?? 1));

$where  = ['1'];
$params = [];
if ($status !== '') {
    $where[]  = 'p.Status = ?';
    $params[] = $status;
}
if ($category > 0) {
    $where[]  = 'p.CategoryID = ?';
    $params[] = $category;
}
foreach (blog_search_words($query) as $word) {
    $like     = '%' . addcslashes($word, '%_\\') . '%';
    $where[]  = '(p.Title LIKE ? OR p.Excerpt LIKE ? OR p.Content LIKE ?)';
    array_push($params, $like, $like, $like);
}
$sqlWhere = implode(' AND ', $where);

$total = (int) db_value("SELECT COUNT(*) FROM BlogPosts p WHERE $sqlWhere", $params);
$pages = max(1, (int) ceil($total / BLOG_ADMIN_PAGE));
$rows  = db_all(
    "SELECT p.BlogID, p.Title, p.Slug, p.Status, p.IsFeatured, p.PublishedDate, p.LastEditedDate, p.FeaturedImage,
            c.CategoryName, CONCAT(a.Name, ' ', a.Surname) AS AuthorName, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
     FROM BlogPosts p
     LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID
     LEFT JOIN AdminUsers a ON a.AdminUserID = p.AuthorID
     LEFT JOIN AdminUsers u ON u.AdminUserID = p.LastEditedBy
     WHERE $sqlWhere
     ORDER BY FIELD(p.Status, 'Draft', 'Published', 'Archived'), COALESCE(p.PublishedDate, p.CreatedDate) DESC, p.BlogID DESC
     LIMIT " . BLOG_ADMIN_PAGE . ' OFFSET ' . (($page - 1) * BLOG_ADMIN_PAGE),
    $params
);
$counts     = array_column(db_all('SELECT Status, COUNT(*) AS Total FROM BlogPosts GROUP BY Status'), 'Total', 'Status');
$categories = db_all('SELECT CategoryID, CategoryName FROM BlogCategories ORDER BY CategoryName');

$listUrl = static fn(array $change = []): string => url('admin/blogs.php?' . http_build_query(array_filter(
    array_merge(['status' => $status, 'category' => $category ?: null, 'q' => $query], $change),
    static fn($v) => $v !== null && $v !== ''
)));

$pageTitle = 'Blog Posts';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <nav class="admin-filters" aria-label="Filter by status">
            <a class="admin-filter<?= $status === '' ? ' is-active' : '' ?>" href="<?= e($listUrl(['status' => null, 'page' => null])) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>All <span class="admin-filter__count"><?= array_sum($counts) ?></span></a>
<?php foreach ($statuses as $value => $label): ?>
            <a class="admin-filter<?= $status === $value ? ' is-active' : '' ?>" href="<?= e($listUrl(['status' => $value, 'page' => null])) ?>"<?= $status === $value ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="admin-filter__count"><?= (int) ($counts[$value] ?? 0) ?></span></a>
<?php endforeach; ?>
        </nav>
<?php if (can('blogs.create')): ?>
        <a class="btn btn--primary btn--small" href="<?= e(url('admin/blog-edit.php')) ?>">New post</a>
<?php endif; ?>
    </div>

    <form class="media-search" method="get">
<?php if ($status !== ''): ?>
        <input type="hidden" name="status" value="<?= e($status) ?>">
<?php endif; ?>
        <div class="admin-field">
            <label for="blog-search">Search posts</label>
            <input id="blog-search" name="q" type="search" value="<?= e($query) ?>">
        </div>
        <div class="admin-field">
            <label for="blog-category">Category</label>
            <select id="blog-category" name="category">
                <option value="">All categories</option>
<?php foreach ($categories as $option): ?>
                <option value="<?= (int) $option['CategoryID'] ?>"<?= (int) $option['CategoryID'] === $category ? ' selected' : '' ?>><?= e($option['CategoryName']) ?></option>
<?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn--outline btn--small" type="submit">Search</button>
    </form>

<?php if ($rows === []): ?>
    <?= admin_empty_state($status === '' && $category === 0 && $query === '' ? 'No posts yet' : 'No posts found', $status === '' && $category === 0 && $query === '' ? 'Write your first article with "New post". It stays a draft until it is published.' : 'Try a different search or filter.', 'pen') ?>
<?php else: ?>
    <p class="admin-meta"><?= $total ?> post<?= $total === 1 ? '' : 's' ?></p>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col"><span class="visually-hidden">Image</span></th>
                    <th scope="col">Title</th>
                    <th scope="col">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col">Published</th>
                    <th scope="col">Last edited</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['FeaturedImage'] ? '<img class="admin-thumb admin-thumb--small" src="' . e(image_url($row['FeaturedImage'], 'thumb')) . '" alt="">' : '' ?></td>
                    <td><?= e($row['Title']) ?><?= $row['IsFeatured'] ? ' <span class="admin-badge">Featured</span>' : '' ?><br><span class="admin-muted">by <?= e(trim((string) $row['AuthorName']) ?: 'a former user') ?></span></td>
                    <td><?= $row['CategoryName'] ? e($row['CategoryName']) : '<span class="admin-muted">None</span>' ?></td>
                    <td><span class="admin-badge admin-badge--<?= e(strtolower($row['Status'])) ?>"><?= e($row['Status']) ?></span></td>
                    <td><?= $row['PublishedDate'] ? e(date('j M Y', strtotime($row['PublishedDate']))) : '<span class="admin-muted">Not yet</span>' ?></td>
                    <td><?= cms_last_edited_cell($row) ?></td>
                    <td class="admin-table__actions">
                        <a class="btn btn--outline btn--small" href="<?= e(url('admin/blog-edit.php?id=' . (int) $row['BlogID'])) ?>">Edit<span class="visually-hidden"> <?= e($row['Title']) ?></span></a>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php if ($pages > 1): ?>
    <nav class="admin-actions" aria-label="Pages">
<?php if ($page > 1): ?>
        <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page - 1])) ?>">Previous</a>
<?php endif; ?>
        <span class="admin-meta">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
        <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page + 1])) ?>">Next</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
