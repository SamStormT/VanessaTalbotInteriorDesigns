<?php
/**
 * Blog Posts: write a new post, or edit one.
 *
 * Editors write and edit; the post stays a Draft until an Admin publishes
 * it (the Status and Featured fields need blogs.publish). The author is the
 * person who created the post.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/blog.php';

$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard('blogs.' . ($deleting ? 'delete' : ($creating ? 'create' : 'edit')));

$module = blog_post_module();
$id     = $creating ? 0 : (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$row    = [];
if (!$creating) {
    $row = db_row(
        "SELECT p.*, CONCAT(a.Name, ' ', a.Surname) AS AuthorName, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
         FROM BlogPosts p
         LEFT JOIN AdminUsers a ON a.AdminUserID = p.AuthorID
         LEFT JOIN AdminUsers u ON u.AdminUserID = p.LastEditedBy
         WHERE p.BlogID = ?",
        [$id]
    );
    if ($row === null) {
        admin_stop(404, 'Post not found', 'That blog post does not exist.');
    }
}

$list = url('admin/blogs.php');

if ($deleting && !$creating) {
    cms_delete($module, $id);
    flash_set('success', 'Post deleted.');
    header('Location: ' . $list);
    exit;
}

$errors = [];
if ($posted) {
    [$data, $errors] = cms_validate($module, $_POST, $row);
    if (array_key_exists('CategoryID', $data)) {
        $data['CategoryID'] = $data['CategoryID'] === null ? null : (int) $data['CategoryID'];
    }

    if ($errors === []) {
        try {
            if ($creating) {
                $newId = cms_create($module, $data + ['AuthorID' => (int) current_admin()['AdminUserID']]);
                blog_after_save($newId, 'Draft', (string) ($data['Status'] ?? 'Draft'));
                media_learn_alt_text($module, $data);
                flash_set('success', ($data['Status'] ?? 'Draft') === 'Published' ? 'Post published.' : 'Draft saved. It is not on the website until it is published.');
                header('Location: ' . url('admin/blog-edit.php?id=' . $newId));
                exit;
            }
            $changed = cms_save($module, $id, $data, $row);
            blog_after_save($id, (string) $row['Status'], (string) ($data['Status'] ?? $row['Status']));
            media_learn_alt_text($module, $data);
            flash_set('success', $changed === [] ? 'No changes to save.' : 'Your changes have been saved.');
            header('Location: ' . url('admin/blog-edit.php?id=' . $id));
            exit;
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $errors['Slug'] = 'Another post already uses this web address. Change the title or the web address.';
        }
    }
}

$pageTitle = $creating ? 'New blog post' : 'Edit: ' . $row['Title'];
$layout    = 'admin';
$activeNav = 'admin/blogs.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($list) ?>">All posts</a>
<?php if (!$creating): ?>
        <a class="btn btn--outline btn--small" href="<?= e(blog_post_url((string) $row['Slug'])) ?>" target="_blank" rel="noopener"><?= $row['Status'] === 'Published' ? 'View on website' : 'Preview' ?><span class="visually-hidden"> (opens in a new tab)</span></a>
<?php if ($row['Status'] === 'Published' && can('subscribers.create')): ?>
        <a class="btn btn--primary btn--small" href="<?= e(url('admin/campaign-edit.php?from_post=' . (int) $row['BlogID'])) ?>">Create campaign from this post</a>
<?php endif; ?>
<?php endif; ?>
    </div>

<?php if (!$creating): ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Post</h2>
        <dl class="admin-fields">
            <div><dt>Status</dt><dd><span class="admin-badge admin-badge--<?= e(strtolower($row['Status'])) ?>"><?= e($row['Status']) ?></span><?= $row['Status'] === 'Published' ? '' : ' <span class="admin-muted">Only signed-in staff can see it.</span>' ?></dd></div>
            <div><dt>Author</dt><dd><?= e(trim((string) $row['AuthorName']) ?: 'A former user') ?></dd></div>
            <div><dt>Published</dt><dd><?= $row['PublishedDate'] ? e(cms_format_date($row['PublishedDate'])) : '<span class="admin-muted">Not yet</span>' ?></dd></div>
            <div><dt>Last edited</dt><dd><?= cms_last_edited_cell($row) ?></dd></div>
        </dl>
    </section>
<?php endif; ?>

<?php if (!can('blogs.publish')): ?>
    <p class="admin-help">You can write and edit posts. An Admin publishes them.<?= !$creating && $row['Status'] === 'Published' ? ' This post is already published, so your changes appear on the website as soon as you save.' : '' ?></p>
<?php endif; ?>

    <?= cms_render_form($module, $creating ? ['Status' => 'Draft'] : $row, $errors, $list, $posted) ?>

<?php if (!$creating && can('blogs.delete')): ?>
    <?= cms_delete_form((string) $row['Title']) ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
