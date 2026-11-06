<?php
/**
 * Admin: Blog Posts (placeholder)
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_admin();

$pageTitle = 'Blog Posts';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content admin-page--blogs">
    <p class="placeholder-note">Placeholder: Blog Posts management coming in a later phase.</p>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
