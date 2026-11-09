<?php
/**
 * Page Sections: add or edit one item in a section.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

$module   = cms_module('page_section_items');
$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard('page_sections.' . ($deleting ? 'delete' : ($creating ? 'create' : 'edit')));

$row = $creating ? [] : cms_load($module, filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
if ($row === null) {
    admin_stop(404, 'Item not found', 'That item does not exist.');
}

$sectionId = $creating ? (filter_input(INPUT_GET, 'section', FILTER_VALIDATE_INT) ?: 0) : (int) $row['SectionID'];
$section   = db_row('SELECT SectionID, PageKey, SectionKey FROM PageSections WHERE SectionID = ?', [$sectionId]);
if ($section === null) {
    admin_stop(404, 'Section not found', 'That section does not exist.');
}

$back = url('admin/section-edit.php?id=' . $sectionId);

if ($deleting && !$creating) {
    cms_delete($module, (int) $row['ItemID']);
    flash_set('success', 'Item deleted.');
    header('Location: ' . $back);
    exit;
}

$errors = [];
if ($posted) {
    $errors = $creating
        ? cms_handle_create($module, $back, ['SectionID' => $sectionId])
        : cms_handle_post($module, (int) $row['ItemID'], $row, $back);
}

$sectionName = cms_page_label($section['PageKey']) . ': ' . cms_section_label($section['SectionKey']);
$pageTitle   = ($creating ? 'Add item to ' : 'Edit item in ') . $sectionName;
$layout      = 'admin';
$activeNav   = 'admin/page-sections.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
<?php if (!$creating): ?>
    <div class="admin-toolbar"><?= cms_last_edited($row) ?></div>
<?php endif; ?>
    <?= cms_render_form($module, $row, $errors, $back, $posted) ?>
<?php if (!$creating && can('page_sections.delete')): ?>
    <?= cms_delete_form((string) ($row['Title'] ?? 'this item')) ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
