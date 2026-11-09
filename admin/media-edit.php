<?php
/**
 * Media Library: preview one image, edit its details, see where it is used.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard($deleting ? 'media.delete' : ($posted ? 'media.edit' : 'media.view'));

$module = cms_module('media');
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$row    = $id > 0 ? cms_load($module, $id) : null;

if ($row === null) {
    admin_stop(404, 'Image not found', 'That image does not exist.');
}

$list  = url('admin/media-library.php');
$usage = media_usage($row['FilePath']);

if ($deleting) {
    if ($usage !== []) {
        flash_set('error', 'This image is still in use, so it cannot be deleted.');
        header('Location: ' . url('admin/media-edit.php?id=' . $id));
        exit;
    }
    media_delete($row);
    flash_set('success', 'Image deleted.');
    header('Location: ' . $list);
    exit;
}

$errors = $posted ? cms_handle_post($module, $id, $row, $list) : [];

$pageTitle = $row['FileName'];
$layout    = 'admin';
$activeNav = 'admin/media-library.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar"><?= cms_last_edited($row) ?></div>

    <section class="admin-panel media-detail">
        <img class="media-detail__image" src="<?= e(url($row['FilePath'])) ?>" alt="<?= e($row['AltText'] ?? '') ?>">
        <dl class="admin-fields">
            <div><dt>Size</dt><dd><?= (int) $row['Width'] ?> × <?= (int) $row['Height'] ?> pixels, <?= e(number_format(((int) $row['FileSize']) / 1048576, 1)) ?> MB</dd></div>
            <div><dt>Uploaded</dt><dd><?= e(cms_format_date($row['UploadDate'])) ?></dd></div>
            <div><dt>Used on</dt><dd><?= $usage === [] ? '<span class="admin-muted">Not in use</span>' : e(implode(', ', $usage)) ?></dd></div>
        </dl>
    </section>

<?php if (can('media.edit')): ?>
    <?= cms_render_form($module, $row, $errors, $list, $posted) ?>
<?php endif; ?>

<?php if (can('media.delete')): ?>
<?php if ($usage === []): ?>
    <?= cms_delete_form((string) $row['FileName']) ?>
<?php else: ?>
    <p class="admin-help">This image is in use, so it cannot be deleted. Remove it from the pages above first.</p>
<?php endif; ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
