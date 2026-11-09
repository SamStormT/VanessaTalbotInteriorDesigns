<?php
/**
 * Page Sections: edit one section and see its items.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

admin_guard('page_sections.edit');

$module = cms_module('page_sections');
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$row    = $id > 0 ? cms_load($module, $id) : null;

if ($row === null) {
    admin_stop(404, 'Section not found', 'That section does not exist.');
}

$self   = url('admin/section-edit.php?id=' . $id);
$list   = url('admin/page-sections.php');
$posted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$errors = $posted ? cms_handle_post($module, $id, $row, $self) : [];

$items = db_all(
    "SELECT i.*, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
     FROM PageSectionItems i LEFT JOIN AdminUsers u ON u.AdminUserID = i.LastEditedBy
     WHERE i.SectionID = ? ORDER BY i.DisplayOrder, i.ItemID",
    [$id]
);

$pageTitle = cms_page_label($row['PageKey']) . ': ' . cms_section_label($row['SectionKey']);
$layout    = 'admin';
$activeNav = 'admin/page-sections.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar"><?= cms_last_edited($row) ?></div>
<?php if ($row['SectionKey'] === 'seo'): ?>
    <p class="admin-alert">This section is for search engines. Heading is the browser title and Introduction is the page description.</p>
<?php endif; ?>

    <?= cms_render_form($module, $row, $errors, $list, $posted) ?>

    <h2 class="admin-section-title">Items in this section</h2>
<?php if (can('page_sections.create')): ?>
    <div class="admin-toolbar admin-toolbar--end"><a class="btn btn--primary btn--small" href="<?= e(url('admin/item-edit.php?section=' . $id)) ?>">Add item</a></div>
<?php endif; ?>
<?php if ($items === []): ?>
    <?= admin_empty_state('No items', 'This section has no repeating items.', 'pen') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Text</th>
                    <th scope="col">Order</th>
                    <th scope="col">Status</th>
                    <th scope="col">Last edited</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['Title'] ?? '') ?></td>
                    <td><?= e(mb_strimwidth((string) ($item['Text'] ?? ''), 0, 70, '…')) ?></td>
                    <td><?= (int) $item['DisplayOrder'] ?></td>
                    <td><span class="admin-badge admin-badge--<?= e(strtolower($item['Status'])) ?>"><?= e($item['Status']) ?></span></td>
                    <td><?= cms_last_edited_cell($item) ?></td>
                    <td class="admin-table__actions"><a class="btn btn--outline btn--small" href="<?= e(url('admin/item-edit.php?id=' . (int) $item['ItemID'])) ?>">Edit<span class="visually-hidden"> <?= e($item['Title'] ?? 'item') ?></span></a></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
