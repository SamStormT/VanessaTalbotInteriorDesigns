<?php
/**
 * Portfolio Projects: add and edit, with the project's gallery images.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

cms_record_edit_page('portfolio', static function (array $project): void {
    $id     = (int) $project['ProjectID'];
    $images = db_all('SELECT * FROM ProjectImages WHERE ProjectID = ? ORDER BY DisplayOrder, ImageID', [$id]);

    echo '<h2 class="admin-section-title">Gallery images</h2>';
    if (can('portfolio.create')) {
        echo '<div class="admin-toolbar admin-toolbar--end"><a class="btn btn--primary btn--small" href="', e(url('admin/project-image-edit.php?project=' . $id)), '">Add image</a></div>';
    }
    if ($images === []) {
        echo admin_empty_state('No gallery images', 'Images added here appear on the project page.', 'camera');
        return;
    }
    echo '<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th scope="col">Image</th><th scope="col">Caption</th><th scope="col">Order</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody>';
    foreach ($images as $image) {
        echo '<tr><td>', e($image['ImagePath']), '</td><td>', e($image['Caption'] ?? ''), '</td><td>', (int) $image['DisplayOrder'], '</td>',
             '<td class="admin-table__actions"><a class="btn btn--outline btn--small" href="', e(url('admin/project-image-edit.php?id=' . (int) $image['ImageID'])), '">Edit<span class="visually-hidden"> ', e($image['ImagePath']), '</span></a></td></tr>';
    }
    echo '</tbody></table></div>';
});
