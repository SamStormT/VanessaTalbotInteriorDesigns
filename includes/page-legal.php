<?php
/**
 * Layout for a legal page. Each file sets $legalSlug. Wording comes from SitePages.
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/content.php';

$page = site_page($legalSlug);
if ($page === null) {
    content_not_found();
}

$pageTitle       = ($page['MetaTitle'] ?? '') ?: $page['Title'];
$pageDescription = ($page['MetaDescription'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero(['Heading' => $page['Title']], [], edit_block('legal-' . $legalSlug, 'content')) ?>

    <section class="section" aria-label="<?= e($page['Title']) ?>">
        <div class="container">
            <div class="content-document">
                <?= content_document($page['Content']) ?>
            </div>
        </div>
    </section>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
