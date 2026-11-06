<?php
/**
 * Admin: New Blog Post (placeholder)
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_admin();

$pageTitle = 'New Blog Post';
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content admin-page--blog-create">
    <p class="placeholder-note">Placeholder: New Blog Post management coming in a later phase.</p>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
