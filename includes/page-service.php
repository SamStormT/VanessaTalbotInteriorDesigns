<?php
/**
 * Layout for an individual service page. Each file in /services sets
 * $serviceSlug and includes this. Wording comes from Services and from
 * PageSections under the page key "service-{slug}".
 */
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/content.php';

$service = site_service($serviceSlug);
if ($service === null) {
    content_not_found();
}

$sections = site_sections('service-' . $serviceSlug);
$closing  = $sections['closing'] ?? null;
$extra    = array_filter($sections, static fn(string $key): bool => !in_array($key, ['closing', 'seo'], true), ARRAY_FILTER_USE_KEY);
$ideal    = content_lines($service['IdealFor']);
$included = content_lines($service['IncludedItems']);

$pageTitle       = ($service['MetaTitle'] ?? '') ?: $service['ServiceName'];
$pageDescription = ($service['MetaDescription'] ?? '') ?: (($service['ShortDescription'] ?? '') ?: SITE_TAGLINE);

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <section class="page-hero" aria-labelledby="page-title">
        <?= edit_block('service-' . $serviceSlug, 'page-header') ?>
        <?= botanical('botanical page-hero__botanical') ?>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li><a href="<?= e(url('services.php')) ?>">Services</a></li>
                    <li aria-current="page"><?= e($service['ServiceName']) ?></li>
                </ol>
            </nav>
            <h1 id="page-title"><?= e($service['ServiceName']) ?></h1>
            <p class="page-hero__lead"><?= e($service['Lead'] ?? '') ?></p>
        </div>
    </section>

    <section class="section" aria-label="<?= e($service['ServiceName']) ?>">
        <?= edit_block('service-' . $serviceSlug, 'main') ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--portrait art-frame--arch art-frame--peach', $service['FeaturedImage'] ?? '', $service['FeaturedImageAlt'] ?? '', icon((string) $service['Icon'], 'icon art-frame__icon')) ?>
            </div>
            <div class="split__content">
                <?= content_paragraphs($service['FullDescription'], 'lead') ?>
<?php if ($included): ?>
                <h2 class="subheading">What's included</h2>
                <ul class="check-list">
<?php foreach ($included as $item): ?>
                    <li><?= icon('check') ?><span><?= e($item) ?></span></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
<?php if ($ideal): ?>
                <h2 class="subheading">Ideal for</h2>
                <ul class="tag-list" aria-label="<?= e($service['ServiceName']) ?> is ideal for">
<?php foreach ($ideal as $market): ?>
                    <li><?= e($market) ?></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
            </div>
        </div>
    </section>

<?php $index = 0; foreach ($extra as $key => $section): ?>
    <?= content_items_section($section, 'section-' . $key, $index++ % 2 === 0, edit_block('service-' . $serviceSlug, (string) $key)) ?>
<?php endforeach; ?>

<?php if ($serviceSlug === 'speaking-engagements' && ($engagements = site_engagements())): ?>
    <section class="section" aria-label="Engagements">
        <?= edit_block('service-' . $serviceSlug, 'engagements') ?>
        <div class="container">
            <ul class="check-list content-narrow">
<?php foreach ($engagements as $event):
    $detail = array_filter([$event['Venue'] ?? '', $event['City'] ?? '', !empty($event['EventDate']) ? date('j F Y', strtotime($event['EventDate'])) : '']);
?>
                <li><?= icon('check') ?><span><strong><?= e($event['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

    <?= $closing ? content_closing($closing, 'service-cta-title', edit_block('service-' . $serviceSlug, 'closing')) : '' ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
