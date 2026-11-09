<?php
/**
 * Page Sections: every section, grouped by page.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

admin_guard('page_sections.view');

$rows = db_all(
    "SELECT s.*, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName,
            (SELECT COUNT(*) FROM PageSectionItems i WHERE i.SectionID = s.SectionID) AS ItemCount
     FROM PageSections s LEFT JOIN AdminUsers u ON u.AdminUserID = s.LastEditedBy
     ORDER BY FIELD(s.PageKey, 'home', 'about', 'services', 'global') = 0, FIELD(s.PageKey, 'home', 'about', 'services', 'global'), s.PageKey, s.DisplayOrder"
);

$pages = [];
foreach ($rows as $row) {
    $pages[$row['PageKey']][] = $row;
}

$pageTitle = 'Page Sections';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
<?php if ($pages === []): ?>
    <?= admin_empty_state('No sections yet', 'Run the database setup to add the existing page sections.') ?>
<?php endif; ?>
<?php foreach ($pages as $page => $sections): ?>
    <h2 class="admin-section-title"><?= e(cms_page_label($page)) ?></h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Section</th>
                    <th scope="col">Heading</th>
                    <th scope="col">Items</th>
                    <th scope="col">Status</th>
                    <th scope="col">Last edited</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($sections as $section): ?>
                <tr>
                    <td><?= e(cms_section_label($section['SectionKey'])) ?></td>
                    <td><?= $section['Heading'] === null ? '<span class="admin-muted">None</span>' : e(mb_strimwidth($section['Heading'], 0, 60, '…')) ?></td>
                    <td><?= (int) $section['ItemCount'] ?></td>
                    <td><span class="admin-badge admin-badge--<?= e(strtolower($section['Status'])) ?>"><?= e($section['Status']) ?></span></td>
                    <td><?= cms_last_edited_cell($section) ?></td>
                    <td class="admin-table__actions">
<?php if (can('page_sections.edit')): ?>
                        <a class="btn btn--outline btn--small" href="<?= e(url('admin/section-edit.php?id=' . (int) $section['SectionID'])) ?>">Edit<span class="visually-hidden"> <?= e(cms_section_label($section['SectionKey'])) ?></span></a>
<?php endif; ?>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
