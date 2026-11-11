<?php
/**
 * Portfolio landing page. All wording comes from the CMS.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections = site_sections('portfolio');
$header   = $sections['page-header'] ?? [];
$featured = site_projects(null, true);

$pageTitle       = $header['Heading'] ?? '';
$pageDescription = ($header['Introduction'] ?? '') ?: SITE_TAGLINE;

$tones = ['green', 'gold', 'peach', 'light'];

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, [], edit_block('portfolio', 'page-header')) ?>

<?php if ($section = $sections['categories'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'categories-title') ?>>
        <?= edit_block('portfolio', 'categories') ?>
        <div class="container">
            <?= content_section_header($section, 'categories-title') ?>
            <ul class="portfolio-grid portfolio-grid--4">
<?php foreach ($section['items'] as $index => $item): ?>
                <li class="portfolio-tile">
                    <?= content_frame('art-frame art-frame--' . $tones[$index % 4], $item['Image'] ?? '', $item['ImageAlt'] ?? '', icon((string) $item['Icon'], 'icon art-frame__icon')) ?>
                    <div class="portfolio-tile__body">
                        <h3 class="portfolio-tile__title">
                            <a class="card__link" href="<?= e(content_url($item['Link'])) ?>"><?= e($item['Title']) ?></a>
                        </h3>
                        <p><?= e($item['Text']) ?></p>
                    </div>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

<?php if ($featured && ($section = $sections['featured'] ?? null)): ?>
    <section class="section section--peach"<?= content_labelled($section, 'featured-title') ?>>
        <?= edit_block('portfolio', 'featured') ?>
        <div class="container">
            <?= content_section_header($section, 'featured-title') ?>
            <ul class="portfolio-grid">
<?php foreach ($featured as $index => $project): ?>
                <?= content_project_tile($project, $index) ?>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

    <?= isset($sections['closing']) ? content_closing($sections['closing'], 'portfolio-cta-title', edit_block('portfolio', 'closing')) : '' ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
