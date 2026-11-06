<?php
/**
 * Admin: Dashboard (placeholder)
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_admin();

$pageTitle = 'Dashboard';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content admin-page--dashboard">
    <p class="placeholder-note">Placeholder: Dashboard management coming in a later phase.</p>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
