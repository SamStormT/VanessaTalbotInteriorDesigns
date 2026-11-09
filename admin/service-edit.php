<?php
/**
 * Services: edit view.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

admin_guard('services.edit');

$module = cms_module('services');
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$row    = $id > 0 ? cms_load($module, $id) : null;

if ($row === null) {
    admin_stop(404, 'Service not found', 'That service does not exist.');
}

$list   = url('admin/services.php');
$posted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$errors = $posted ? cms_handle_post($module, $id, $row, $list) : [];

$pageTitle = 'Edit: ' . $row['ServiceName'];
$layout    = 'admin';
$activeNav = 'admin/services.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <div class="admin-toolbar"><?= cms_last_edited($row) ?></div>
    <?= cms_render_form($module, $row, $errors, $list, $posted) ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
