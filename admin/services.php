<?php
/**
 * Services: list view.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

admin_guard('services.view');

$services = db_all(
    "SELECT s.ServiceID, s.ServiceName, s.Status, s.DisplayOrder, s.LastEditedDate, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
     FROM Services s LEFT JOIN AdminUsers u ON u.AdminUserID = s.LastEditedBy
     ORDER BY s.DisplayOrder, s.ServiceName"
);

$pageTitle = 'Services';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
<?php if ($services === []): ?>
    <?= admin_empty_state('No services yet', 'Run the database setup to add the six existing services.') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Service</th>
                    <th scope="col">Status</th>
                    <th scope="col">Order</th>
                    <th scope="col">Last edited</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($services as $service): ?>
                <tr>
                    <td><?= e($service['ServiceName']) ?></td>
                    <td><span class="admin-badge admin-badge--<?= e(strtolower($service['Status'])) ?>"><?= e($service['Status']) ?></span></td>
                    <td><?= (int) $service['DisplayOrder'] ?></td>
                    <td><?= $service['LastEditedDate'] === null
                        ? '<span class="admin-muted">Not edited yet</span>'
                        : e(trim((string) $service['LastEditorName']) ?: 'A former user') . '<br><span class="admin-muted">' . e(cms_format_date($service['LastEditedDate'])) . '</span>' ?></td>
                    <td class="admin-table__actions">
<?php if (can('services.edit')): ?>
                        <a class="btn btn--outline btn--small" href="<?= e(url('admin/service-edit.php?id=' . (int) $service['ServiceID'])) ?>">Edit<span class="visually-hidden"> <?= e($service['ServiceName']) ?></span></a>
<?php endif; ?>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
