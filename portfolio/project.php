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

$images = site_project_images((int) $project['ProjectID']);

$pageTitle       = ($project['MetaTitle'] ?? '') ?: $project['Title'];
$pageDescription = ($project['MetaDescription'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero(['Heading' => $project['Title'], 'Introduction' => $project['Location'] ?? ''], [['Portfolio', 'portfolio.php']]) ?>

    <section class="section" aria-label="<?= e($project['Title']) ?>">
        <?= edit_block('project', 'project', [['admin', 'Edit this project', 'admin/project-edit.php?id=' . (int) $project['ProjectID'], 'portfolio.edit']]) ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--peach', $project['FeaturedImage'] ?? '', $project['FeaturedImageAlt'] ?? '', icon('leaf', 'icon art-frame__icon')) ?>
            </div>
            <div class="split__content">
                <?= content_paragraphs($project['Description'], 'lead') ?>
            </div>
        </div>
    </section>

<?php if ($images): ?>
    <section class="section section--peach" aria-label="Gallery">
        <div class="container">
            <ul class="portfolio-grid">
<?php foreach ($images as $image): ?>
                <li class="portfolio-tile">
                    <?= content_frame('art-frame', $image['ImagePath'], $image['AltText'] ?? '', '') ?>
<?php if (!empty($image['Caption'])): ?>
                    <div class="portfolio-tile__body"><p><?= e($image['Caption']) ?></p></div>
<?php endif; ?>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
