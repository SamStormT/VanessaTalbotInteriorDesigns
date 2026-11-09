<?php
/**
 * Layout for a portfolio category. Each file sets $portfolioCategory (the
 * stored category value) and $portfolioPage (its PageSections page key).
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections = site_sections($portfolioPage);
$header   = $sections['page-header'] ?? [];
$projects = site_projects($portfolioCategory);

$pageTitle       = $header['Heading'] ?? '';
$pageDescription = ($header['Introduction'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, [['Portfolio', 'portfolio.php']]) ?>

<?php if ($section = $sections['intro'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'intro-title') ?>>
        <div class="container">
            <?= content_section_header($section, 'intro-title') ?>
            <div class="content-narrow"><?= content_paragraphs($section['Body']) ?></div>
        </div>
    </section>
<?php endif; ?>

    <section class="section section--peach" aria-label="Projects">
        <div class="container">
<?php if ($projects): ?>
            <ul class="portfolio-grid">
<?php foreach ($projects as $index => $project): ?>
                <?= content_project_tile($project, $index) ?>
<?php endforeach; ?>
            </ul>
<?php elseif ($empty = $sections['empty'] ?? null): ?>
            <?= content_empty_state($empty) ?>
<?php endif; ?>
        </div>
    </section>

    <?= isset($sections['closing']) ? content_closing($sections['closing'], 'portfolio-cta-title') : '' ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
