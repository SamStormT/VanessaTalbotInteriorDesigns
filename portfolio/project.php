<?php
/**
 * Single portfolio project, found by ?slug=
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$slug    = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$project = $slug === '' ? null : site_project($slug);
if ($project === null) {
    content_not_found();
}

$images   = site_project_images((int) $project['ProjectID']);
// Images with a "before" photo become before-and-after sliders, shown first.
$compares = array_values(array_filter($images, static fn(array $i): bool => trim((string) ($i['BeforeImage'] ?? '')) !== ''));
$images   = array_values(array_filter($images, static fn(array $i): bool => trim((string) ($i['BeforeImage'] ?? '')) === ''));
$pageImage    = $project['FeaturedImage'] ?? '';
$pageImageAlt = $project['FeaturedImageAlt'] ?? '';
$pagePath     = 'portfolio/project.php?slug=' . $project['Slug'];

$pageTitle       = ($project['MetaTitle'] ?? '') ?: $project['Title'];
$pageDescription = ($project['MetaDescription'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero(['Heading' => $project['Title'], 'Introduction' => $project['Location'] ?? ''], [['Portfolio', 'portfolio.php']], '', ed_record('portfolio', 'admin/project-edit.php', (int) $project['ProjectID'], 'Title', 'portfolio.edit'), ed_record('portfolio', 'admin/project-edit.php', (int) $project['ProjectID'], 'Location', 'portfolio.edit')) ?>

    <section class="section" aria-label="<?= e($project['Title']) ?>">
        <?= edit_block('project', 'project', [['admin', 'Edit this project', 'admin/project-edit.php?id=' . (int) $project['ProjectID'], 'portfolio.edit']]) ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--peach', $project['FeaturedImage'] ?? '', $project['FeaturedImageAlt'] ?? '', icon('leaf', 'icon art-frame__icon'), 'web', ed_record('portfolio', 'admin/project-edit.php', (int) $project['ProjectID'], 'FeaturedImage', 'portfolio.edit')) ?>
            </div>
            <div class="split__content">
                <?= content_paragraphs($project['Description'], 'lead', ed_record('portfolio', 'admin/project-edit.php', (int) $project['ProjectID'], 'Description', 'portfolio.edit')) ?>
            </div>
        </div>
    </section>

<?php if ($compares): ?>
    <section class="section" aria-label="Before and after">
        <div class="container compare-list">
<?php foreach ($compares as $image): ?>
            <?= content_compare($image, ed_record('project_images', 'admin/project-image-edit.php', (int) $image['ImageID'], 'BeforeImage', 'portfolio.edit')) ?>
<?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($images): ?>
    <section class="section section--peach" aria-label="Gallery">
        <div class="container">
            <ul class="portfolio-grid">
<?php foreach ($images as $image): ?>
                <li class="portfolio-tile">
                    <?= content_frame('art-frame', $image['ImagePath'], $image['AltText'] ?? '', '', 'web', ed_record('project_images', 'admin/project-image-edit.php', (int) ($image['ImageID'] ?? 0), 'ImagePath', 'portfolio.edit')) ?>
<?php if (!empty($image['Caption'])): ?>
                    <div class="portfolio-tile__body"><p<?= ed_record('project_images', 'admin/project-image-edit.php', (int) ($image['ImageID'] ?? 0), 'Caption', 'portfolio.edit') ?>><?= e($image['Caption']) ?></p></div>
<?php endif; ?>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

    <?= content_testimonials_section(site_testimonials(null, (int) $project['ProjectID'], 2), 'testimonials-title') ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
