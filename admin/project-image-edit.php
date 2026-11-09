<?php
/**
 * Portfolio Projects: add or edit one gallery image.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

$module   = cms_module('project_images');
$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard('portfolio.' . ($deleting ? 'delete' : ($creating ? 'create' : 'edit')));

$row = $creating ? [] : cms_load($module, filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
if ($row === null) {
    admin_stop(404, 'Image not found', 'That image does not exist.');
}

$projectId = $creating ? (filter_input(INPUT_GET, 'project', FILTER_VALIDATE_INT) ?: 0) : (int) $row['ProjectID'];
$project   = db_row('SELECT ProjectID, Title FROM PortfolioProjects WHERE ProjectID = ?', [$projectId]);
if ($project === null) {
    admin_stop(404, 'Project not found', 'That project does not exist.');
}

$back = url('admin/project-edit.php?id=' . $projectId);

if ($deleting && !$creating) {
    cms_delete($module, (int) $row['ImageID']);
    flash_set('success', 'Image removed.');
    header('Location: ' . $back);
    exit;
}

$errors = [];
if ($posted) {
    $errors = $creating
        ? cms_handle_create($module, $back, ['ProjectID' => $projectId])
        : cms_handle_post($module, (int) $row['ImageID'], $row, $back);
}

$pageTitle = ($creating ? 'Add image to ' : 'Edit image in ') . $project['Title'];
$layout    = 'admin';
$activeNav = 'admin/portfolio-projects.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
<?php if (!$creating): ?>
    <div class="admin-toolbar"><?= cms_last_edited($row) ?></div>
<?php endif; ?>
    <?= cms_render_form($module, $row, $errors, $back, $posted) ?>
<?php if (!$creating && can('portfolio.delete')): ?>
    <?= cms_delete_form('this image') ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
