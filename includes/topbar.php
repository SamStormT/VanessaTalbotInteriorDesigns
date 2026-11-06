<?php
/**
 * Admin top bar (page title + account menu placeholder).
 */
?>
<header class="admin-topbar">
    <button class="admin-topbar__menu" type="button" aria-controls="admin-sidebar" aria-expanded="false">
        <span class="visually-hidden">Toggle sidebar</span>&#9776;
    </button>
    <h1 class="admin-topbar__title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    <div class="admin-topbar__account">
        <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
        <!-- Phase 2+: logged-in user name and logout link -->
    </div>
</header>
