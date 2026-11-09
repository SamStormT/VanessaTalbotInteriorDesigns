<?php
/**
 * Services overview page. All wording comes from the CMS (see CONTENT_MAPPING.md).
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections = site_sections('services');
$services = site_services();
$header   = $sections['page-header'] ?? [];

$pageTitle       = ($sections['seo']['Heading'] ?? '') ?: ($header['Heading'] ?? '');
$pageDescription = ($sections['seo']['Introduction'] ?? '') ?: SITE_TAGLINE;

$tones = ['green', 'peach', 'gold'];

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <section class="page-hero" aria-labelledby="page-title">
        <?= botanical('botanical page-hero__botanical') ?>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li aria-current="page">Services</li>
                </ol>
            </nav>
            <h1 id="page-title"><?= e($header['Heading'] ?? '') ?></h1>
            <p class="page-hero__lead"><?= e($header['Introduction'] ?? '') ?></p>
        </div>
    </section>

    <nav class="service-nav" aria-label="Jump to a service">
        <div class="container">
            <ul class="service-nav__list">
<?php foreach ($services as $service): ?>
                <li><a href="#<?= e($service['Slug']) ?>"><?= icon((string) $service['Icon'], 'icon icon--inline') ?> <?= e($service['ServiceName']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </div>
    </nav>

<?php foreach ($services as $i => $service):
    $name  = (string) $service['ServiceName'];
    $slug  = (string) $service['Slug'];
    $ideal = content_lines($service['IdealFor']);
?>
    <section id="<?= e($slug) ?>" class="section service-section<?= $i % 2 ? ' section--peach' : '' ?>" aria-labelledby="<?= e($slug) ?>-title">
        <div class="container split<?= $i % 2 ? ' split--reverse' : '' ?>">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--' . $tones[$i % 3], $service['FeaturedImage'] ?? '', $service['FeaturedImageAlt'] ?? '', icon((string) $service['Icon'], 'icon art-frame__icon')) ?>
            </div>
            <div class="split__content">
                <p class="eyebrow"><span class="service-section__number"><?= sprintf('%02d', $i + 1) ?></span> Service</p>
                <h2 id="<?= e($slug) ?>-title"><?= e($name) ?></h2>
                <?= content_paragraphs($service['Lead'], 'lead') ?>
                <?= content_paragraphs($service['FullDescription']) ?>

                <h3 class="subheading">What's included</h3>
                <ul class="check-list">
<?php foreach (content_lines($service['IncludedItems']) as $item): ?>
                    <li><?= icon('check') ?><span><?= e($item) ?></span></li>
<?php endforeach; ?>
                </ul>

                <h3 class="subheading">Ideal for</h3>
                <ul class="tag-list" aria-label="<?= e($name) ?> is ideal for">
<?php foreach ($ideal as $market): ?>
                    <li><?= e($market) ?></li>
<?php endforeach; ?>
                </ul>

                <div class="btn-group">
                    <a class="btn btn--dark" href="<?= e(service_page_url($slug)) ?>">
                        More about <?= e(strtolower($name)) ?>
                    </a>
                    <a class="btn btn--outline" href="<?= e(url('contact.php?service=' . urlencode($slug))) ?>">
                        Enquire<span class="visually-hidden"> about <?= e(strtolower($name)) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endforeach; ?>

    <!-- Process -->
<?php if ($section = $sections['process'] ?? null): ?>
    <section class="section section--white"<?= content_labelled($section, 'process-title') ?>>
        <div class="container">
            <?= content_section_header($section, 'process-title') ?>
            <ol class="process">
<?php foreach ($section['items'] as $step): ?>
                <li class="process__step">
                    <h3><?= e($step['Title']) ?></h3>
                    <p><?= e($step['Text']) ?></p>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>
<?php endif; ?>

    <!-- Call-to-action -->
<?php if ($section = $sections['closing'] ?? null): ?>
    <section class="section cta-banner"<?= content_labelled($section, 'services-cta-title') ?>>
        <div class="container cta-banner__inner">
            <?= content_heading($section, 'services-cta-title') ?>
            <?= content_paragraphs($section['Introduction']) ?>
            <div class="btn-group btn-group--centred">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--primary') ?>
                <?= content_link($section['SecondaryButtonText'], $section['SecondaryButtonLink'], 'btn btn--outline') ?>
            </div>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
