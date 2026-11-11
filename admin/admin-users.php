<?php
/**
 * Users: everyone who can sign in to the admin area.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/users.php';

admin_guard('users.view');

$show = is_string($_GET['show'] ?? null) && in_array($_GET['show'], ['active', 'disabled'], true) ? $_GET['show'] : '';
$rows = db_all(
    "SELECT AdminUserID, Name, Surname, Email, Role, Status, LastLogin, CreatedDate,
            (PasswordChangedDate IS NULL AND LastLogin IS NULL) AS NeverSignedIn
     FROM AdminUsers" . match ($show) { 'active' => " WHERE Status = 'Active'", 'disabled' => " WHERE Status = 'Inactive'", default => '' } . "
     ORDER BY Status = 'Active' DESC, FIELD(Role, 'SuperAdmin', 'Admin', 'Editor'), Name, Surname"
);
$counts = db_row("SELECT COUNT(*) AS everyone, SUM(Status = 'Active') AS active, SUM(Status = 'Inactive') AS disabled FROM AdminUsers");
$me     = current_admin();

$pageTitle = 'Users';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <nav class="admin-filters" aria-label="Filter users">
<?php foreach (['' => ['All', 'everyone'], 'active' => ['Active', 'active'], 'disabled' => ['Disabled', 'disabled']] as $key => [$label, $count]): ?>
            <a class="admin-filter<?= $show === $key ? ' is-active' : '' ?>" href="<?= e(url('admin/admin-users.php' . ($key === '' ? '' : '?show=' . $key))) ?>"<?= $show === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?> <span class="admin-filter__count"><?= (int) $counts[$count] ?></span></a>
<?php endforeach; ?>
        </nav>
<?php if (can('users.create')): ?>
        <a class="btn btn--primary btn--small" href="<?= e(url('admin/user-edit.php')) ?>">Add user</a>
<?php endif; ?>
    </div>

<?php if ($rows === []): ?>
    <?= admin_empty_state('No users here', 'No accounts match this filter.', 'person') ?>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Last signed in</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): $name = trim($row['Name'] . ' ' . $row['Surname']); ?>
                <tr>
                    <td><?= e($name) ?><?= (int) $row['AdminUserID'] === (int) $me['AdminUserID'] ? ' <span class="admin-muted">(you)</span>' : '' ?></td>
                    <td><?= e($row['Email']) ?></td>
                    <td><?= e($row['Role']) ?></td>
                    <td><span class="admin-badge admin-badge--<?= $row['Status'] === 'Active' ? 'subscribed' : 'closed' ?>"><?= $row['Status'] === 'Active' ? 'Active' : 'Disabled' ?></span></td>
                    <td><?= $row['LastLogin'] ? e(cms_format_date($row['LastLogin'])) : '<span class="admin-muted">' . ($row['NeverSignedIn'] ? 'Not yet (invited)' : 'Never') . '</span>' ?></td>
                    <td class="admin-table__actions">
<?php if (can('users.edit')): ?>
                        <a class="btn btn--outline btn--small" href="<?= e(url('admin/user-edit.php?id=' . (int) $row['AdminUserID'])) ?>">Manage<span class="visually-hidden"> <?= e($name) ?></span></a>
<?php endif; ?>
                    </td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

    <section class="admin-panel admin-panel--note">
        <h2 class="admin-panel__title">Roles</h2>
        <dl class="admin-fields">
<?php foreach (user_roles() as $role => $description): ?>
            <div><dt><?= e($role) ?></dt><dd><?= e($description) ?></dd></div>
<?php endforeach; ?>
        </dl>
    </section>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
