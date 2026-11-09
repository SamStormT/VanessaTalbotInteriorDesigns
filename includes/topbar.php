<?php
/**
 * Admin top bar: page title, signed-in user and sign out.
 */
$admin = current_admin();
?>
<header class="admin-topbar">
    <button class="admin-topbar__menu" type="button" aria-controls="admin-sidebar" aria-expanded="false">
        <span class="visually-hidden">Toggle sidebar</span>&#9776;
    </button>
    <h1 class="admin-topbar__title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    <div class="admin-topbar__account">
<?php if ($admin !== null): ?>
        <span class="admin-topbar__user"><?= e($admin['Name'] . ' ' . $admin['Surname']) ?> <span class="admin-badge"><?= e($admin['Role']) ?></span></span>
<?php endif; ?>
        <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button class="admin-topbar__logout" type="submit">Sign out</button>
        </form>
    </div>
</header>
