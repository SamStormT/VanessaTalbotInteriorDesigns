<?php
/**
 * Home (placeholder)
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle       = '';
$pageDescription = SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="page page--home">
    <section class="section">
        <div class="container">
            <h1 class="display-title"><?= e(SITE_NAME) ?></h1>
            <p class="tagline"><?= e(SITE_TAGLINE) ?></p>
            <p class="placeholder-note">Placeholder: content coming in a later phase.</p>
        </div>
    </section>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
