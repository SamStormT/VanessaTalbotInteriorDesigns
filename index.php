<?php
/**
 * Home page. All wording comes from the CMS (see CONTENT_MAPPING.md).
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/newsletter-form.php';
require_once INCLUDES_PATH . '/blog.php';

$home     = site_home();

$sections = site_sections('home');
$services = site_services();

$pageTitle       = $home['MetaTitle'] ?? '';
$pageDescription = ($home['MetaDescription'] ?? '') ?: SITE_TAGLINE;

// The three newest published blog posts.
$latestPosts = blog_latest(3);

require_once INCLUDES_PATH . '/header.php';

$featureCta = $sections['feature-cta'] ?? [];
$arrow      = ' ' . icon('arrow', 'icon icon--inline');
$tileTones  = ['green', 'gold', 'peach'];
?>
<main id="main-content">

    <!-- Hero -->
    <section class="hero" aria-labelledby="hero-title">
        <?= edit_block('home', 'hero') ?>
        <div class="container hero__inner">
            <div class="hero__content">
                <?php if (!empty($home['HeroEyebrow'])): ?><p class="eyebrow"<?= ed_home('HeroEyebrow') ?>><?= e($home['HeroEyebrow']) ?></p><?php endif; ?>
                <h1 id="hero-title" class="display-title hero__title"<?= ed_home('HeroTitle') ?>><?= e($home['HeroTitle'] ?? '') ?></h1>
                <p class="tagline hero__tagline"<?= ed_setting('Tagline') ?>><?= e(site_tagline()) ?></p>
                <p class="hero__lead"<?= ed_home('HeroSubtitle') ?>><?= e($home['HeroSubtitle'] ?? '') ?></p>
                <div class="btn-group">
                    <?= content_link($home['CTA1Text'] ?? '', $home['CTA1Link'] ?? '', 'btn btn--primary', '', ed_home('CTA1Text')) ?>
                    <?= content_link($home['CTA2Text'] ?? '', $home['CTA2Link'] ?? '', 'btn btn--outline', '', ed_home('CTA2Text')) ?>
                </div>
            </div>
            <div class="hero__media"<?= empty($home['HeroImage']) ? ' aria-hidden="true"' : '' ?>>
                <?= empty($home['HeroImage'])
                    ? '<div class="art-frame art-frame--arch art-frame--peach"' . ed_home('HeroImage') . '>' . botanical('botanical art-frame__botanical') . '</div>'
                    : content_frame('art-frame art-frame--arch art-frame--peach', $home['HeroImage'], $home['HeroImageAlt'] ?? '', '', 'full', ed_home('HeroImage')) ?>
                <span class="hero__accent"></span>
            </div>
        </div>
    </section>

    <!-- Featured call-to-action -->
<?= empty($home['FeaturedCTATitle']) ? hidden_field_block('Featured box', 'no title yet', 'admin/homepage-content.php?edit=1#row-FeaturedCTATitle', 'homepage.edit') : '' ?>
<?php if (!empty($home['FeaturedCTATitle'])): ?>
    <section class="feature-cta" aria-labelledby="feature-cta-title">
        <?= edit_block('home', 'feature-cta') ?>
        <div class="container">
            <div class="feature-cta__card">
                <span class="feature-cta__icon"><?= icon('leaf') ?></span>
                <div class="feature-cta__text">
                    <h2 id="feature-cta-title" class="feature-cta__title"<?= ed_home('FeaturedCTATitle') ?>><?= e($home['FeaturedCTATitle']) ?></h2>
                    <p<?= ed_home('FeaturedCTAText') ?>><?= e($home['FeaturedCTAText'] ?? '') ?></p>
                </div>
                <?= content_link($featureCta['ButtonText'] ?? '', $home['FeaturedCTALink'] ?? '', 'btn btn--primary', $arrow, ed_section($featureCta ?: null, 'ButtonText')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Introduction to Vanessa -->
<?= hidden_block('home', 'introduction', $sections) ?>
<?php if ($section = $sections['introduction'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'intro-title') ?>>
        <?= edit_block('home', 'introduction') ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--portrait art-frame--arch art-frame--light', $home['IntroductionImage'] ?? '', $home['IntroductionImageAlt'] ?? '', botanical('botanical art-frame__botanical'), 'web', ed_home('IntroductionImage')) ?>
            </div>
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'intro-title') ?>
                <?= content_paragraphs($section['Introduction'], 'lead', ed_section($section, 'Introduction')) ?>
                <?= content_paragraphs($section['Body'], '', ed_section($section, 'Body')) ?>
<?php if ($section['items']): ?>
                <ul class="pillars" aria-label="The three foundations of Vanessa's work">
<?php foreach ($section['items'] as $item): ?>
                    <li class="pillar"<?= ed_item($item, 'Title') ?>><?= icon((string) $item['Icon']) ?><span><strong><?= e($item['Title']) ?></strong> <?= e($item['Text']) ?></span></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'text-link', $arrow, ed_section($section, 'ButtonText')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Brand quote -->
<?= empty($home['FeaturedQuote']) ? hidden_field_block('Quote', 'no quote yet', 'admin/homepage-content.php?edit=1#row-FeaturedQuote', 'homepage.edit') : '' ?>
<?php if (!empty($home['FeaturedQuote'])): ?>
    <section class="quote-band" aria-label="Quote">
        <?= edit_block('home', 'quote') ?>
        <div class="container">
            <span class="divider" aria-hidden="true"><?= icon('leaf') ?></span>
            <figure class="quote-band__figure">
                <blockquote>
                    <p<?= ed_home('FeaturedQuote') ?>><?= e($home['FeaturedQuote']) ?></p>
                </blockquote>
                <figcaption><span class="tagline"<?= ed_home('FeaturedQuoteAuthor') ?>><?= e($home['FeaturedQuoteAuthor'] ?? '') ?></span></figcaption>
            </figure>
        </div>
    </section>
<?php endif; ?>

    <!-- Services overview -->
<?= hidden_block('home', 'services', $sections) ?>
<?php if ($section = $sections['services'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'services-title') ?>>
        <?= edit_block('home', 'services') ?>
        <div class="container">
            <?= content_section_header($section, 'services-title') ?>

            <ul class="card-grid card-grid--3">
<?php foreach ($services as $service): ?>
                <li class="card">
                    <span class="card__icon"<?= ed_service($service, 'Icon') ?>><?= icon((string) $service['Icon']) ?></span>
                    <h3 class="card__title"<?= ed_service($service, 'ServiceName') ?>>
                        <a class="card__link" href="<?= e(service_page_url($service['Slug'])) ?>"><?= e($service['ServiceName']) ?></a>
                    </h3>
                    <p<?= ed_service($service, 'ShortDescription') ?>><?= e($service['ShortDescription']) ?></p>
                    <span class="card__more" aria-hidden="true">Learn more <?= icon('arrow', 'icon icon--inline') ?></span>
                </li>
<?php endforeach; ?>
            </ul>

            <p class="section-footer">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--dark', '', ed_section($section, 'ButtonText')) ?>
            </p>
        </div>
    </section>
<?php endif; ?>

    <!-- Featured portfolio -->
<?= hidden_block('home', 'portfolio', $sections) ?>
<?php if ($section = $sections['portfolio'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'portfolio-title') ?>>
        <?= edit_block('home', 'portfolio') ?>
        <div class="container">
            <?= content_section_header($section, 'portfolio-title') ?>

            <ul class="portfolio-grid">
<?php foreach ($section['items'] as $index => $item): ?>
                <li class="portfolio-tile">
                    <?= content_frame('art-frame art-frame--' . $tileTones[$index % 3], $item['Image'] ?? '', $item['ImageAlt'] ?? '', icon((string) $item['Icon'], 'icon art-frame__icon'), 'web', ed_item($item, 'Image')) ?>
                    <div class="portfolio-tile__body">
                        <h3 class="portfolio-tile__title"<?= ed_item($item, 'Title') ?>>
                            <a class="card__link" href="<?= e(content_url($item['Link'])) ?>"><?= e($item['Title']) ?></a>
                        </h3>
                        <p<?= ed_item($item, 'Text') ?>><?= e($item['Text']) ?></p>
                    </div>
                </li>
<?php endforeach; ?>
            </ul>

            <p class="section-footer">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'text-link', $arrow, ed_section($section, 'ButtonText')) ?>
            </p>
        </div>
    </section>
<?php endif; ?>

    <!-- Latest blog -->
<?= hidden_block('home', 'blog', $sections) ?>
<?php if ($section = $sections['blog'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'blog-title') ?>>
        <?= edit_block('home', 'blog') ?>
        <div class="container">
            <?= content_section_header($section, 'blog-title') ?>

<?php if ($latestPosts): ?>
            <ul class="card-grid card-grid--3 blog-grid">
<?php foreach ($latestPosts as $index => $post): ?>
                <?= blog_card($post, $index) ?>
<?php endforeach; ?>
            </ul>
            <p class="section-footer">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--dark', '', ed_section($section, 'ButtonText')) ?>
            </p>
<?php elseif ($empty = $sections['blog-empty'] ?? null): ?>
            <div class="empty-state">
                <span class="empty-state__icon"><?= icon('pen') ?></span>
                <p class="empty-state__title"<?= ed_section($empty, 'Heading') ?>><?= e($empty['Heading']) ?></p>
                <p<?= ed_section($empty, 'Introduction') ?>><?= e($empty['Introduction']) ?></p>
                <ul class="tag-list" aria-label="Upcoming blog topics">
<?php foreach ($empty['items'] as $item): ?>
                    <li<?= ed_item($item, 'Title') ?>><?= e($item['Title']) ?></li>
<?php endforeach; ?>
                </ul>
            </div>
<?php endif; ?>
        </div>
    </section>
<?php endif; ?>

    <!-- Newsletter -->
<?= hidden_block('home', 'newsletter', $sections) ?>
<?php if ($section = $sections['newsletter'] ?? null): ?>
    <section id="newsletter" class="section newsletter"<?= content_labelled($section, 'newsletter-title') ?>>
        <?= edit_block('home', 'newsletter') ?>
        <div class="container newsletter__inner">
            <div class="newsletter__text">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'newsletter-title') ?>
                <?= content_paragraphs($section['Introduction'], '', ed_section($section, 'Introduction')) ?>
            </div>

            <?= newsletter_form('newsletter', 'home', 'index.php', $section['Body'], ed_section($section, 'Body')) ?>
        </div>
    </section>
<?php endif; ?>

    <!-- Contact call-to-action -->
<?= hidden_block('home', 'contact-cta', $sections) ?>
<?php if ($section = $sections['contact-cta'] ?? null): ?>
    <section class="section cta-banner"<?= content_labelled($section, 'contact-cta-title') ?>>
        <?= edit_block('home', 'contact-cta') ?>
        <div class="container cta-banner__inner">
            <?= content_heading($section, 'contact-cta-title') ?>
            <?= content_paragraphs($section['Introduction'], '', ed_section($section, 'Introduction')) ?>
            <ul class="tag-list tag-list--centred" aria-label="Who Vanessa works with">
<?php foreach ($section['items'] as $item): ?>
                <li<?= ed_item($item, 'Title') ?>><?= e($item['Title']) ?></li>
<?php endforeach; ?>
            </ul>
            <div class="btn-group btn-group--centred">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--primary', '', ed_section($section, 'ButtonText')) ?>
                <?= content_link($section['SecondaryButtonText'], $section['SecondaryButtonLink'], 'btn btn--outline', '', ed_section($section, 'SecondaryButtonText')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
