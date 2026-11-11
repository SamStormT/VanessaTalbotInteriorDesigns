<?php
/**
 * About page. All wording comes from the CMS (see CONTENT_MAPPING.md).
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$about          = site_about();
$sections       = site_sections('about');
$values         = site_values();
$qualifications = site_qualifications();
$engagements    = site_engagements();

$pageTitle       = ($about['MetaTitle'] ?? '') ?: ($sections['page-header']['Heading'] ?? '');
$pageDescription = ($about['MetaDescription'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_hero_open($sections['page-header'] ?? [], edit_block('about', 'page-header')) ?>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li aria-current="page">About</li>
                </ol>
            </nav>
            <h1 id="page-title"><?= e($sections['page-header']['Heading'] ?? '') ?></h1>
            <p class="tagline page-hero__tagline"><?= e(site_tagline()) ?></p>
        </div>
    </section>

    <!-- Vanessa's story -->
<?php if ($section = $sections['story'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'story-title') ?>>
        <?= edit_block('about', 'story') ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--portrait art-frame--arch art-frame--peach', $about['PortraitImage'] ?? '', $about['PortraitImageAlt'] ?? '', botanical('botanical art-frame__botanical')) ?>
            </div>
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'story-title') ?>
                <?= content_paragraphs($about['Biography'] ?? '', 'lead') ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Science to interior design journey -->
<?php if ($section = $sections['journey'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'journey-title') ?>>
        <?= edit_block('about', 'journey') ?>
        <div class="container">
            <?= content_section_header($section, 'journey-title') ?>

            <ol class="journey">
<?php foreach ($section['items'] as $item): ?>
                <li class="journey__step">
                    <span class="journey__icon"><?= icon((string) $item['Icon']) ?></span>
                    <h3><?= e($item['Title']) ?></h3>
                    <p><?= e($item['Text']) ?></p>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>
<?php endif; ?>

    <!-- Biophilic design philosophy -->
<?php if ($section = $sections['philosophy'] ?? null):
    $quote = $sections['quote']['items'][0] ?? null;
?>
    <section class="section"<?= content_labelled($section, 'philosophy-title') ?>>
        <?= edit_block('about', 'philosophy') ?>
        <div class="container split split--reverse">
            <div class="split__media">
<?php if ($quote): ?>
                <figure class="quote-panel">
                    <blockquote>
                        <p>&ldquo;<?= e($quote['Text']) ?>&rdquo;</p>
                    </blockquote>
                    <figcaption><span class="tagline"><?= e($quote['Title']) ?></span></figcaption>
                </figure>
<?php endif; ?>
            </div>
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'philosophy-title') ?>
                <?= content_paragraphs($section['Introduction'], 'lead') ?>
                <?= content_paragraphs($section['Body']) ?>
                <ul class="check-list">
<?php foreach ($section['items'] as $item): ?>
                    <li><?= icon('check') ?><span><strong><?= e($item['Title']) ?></strong>: <?= e($item['Text']) ?></span></li>
<?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Values -->
<?php if ($section = $sections['values'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'values-title') ?>>
        <?= edit_block('about', 'values') ?>
        <div class="container">
            <?= content_section_header($section, 'values-title', $about['CoreValues'] ?? '') ?>
            <ul class="values-grid">
<?php foreach ($values as $value): ?>
                <li class="value">
                    <span class="value__icon"><?= icon((string) $value['Icon']) ?></span>
                    <h3 class="value__title"><?= e($value['Title']) ?></h3>
                    <p><?= e($value['Description']) ?></p>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

    <!-- Qualifications -->
<?php if ($section = $sections['qualifications'] ?? null):
    $notice = $sections['qualifications-notice'] ?? null;
?>
    <section class="section"<?= content_labelled($section, 'qualifications-title') ?>>
        <?= edit_block('about', 'qualifications') ?>
        <div class="container">
            <?= content_section_header($section, 'qualifications-title', $about['Qualifications'] ?? '') ?>
<?php if ($qualifications): ?>
            <ul class="check-list content-narrow">
<?php foreach ($qualifications as $item):
    $detail = array_filter([$item['Institution'] ?? '', $item['FieldOfStudy'] ?? '', $item['YearCompleted'] ?? '']);
?>
                <li><?= icon('check') ?><span><strong><?= e($item['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
            </ul>
<?php elseif ($notice): ?>
            <div class="info-panel">
                <span class="info-panel__icon"><?= icon('shield') ?></span>
                <div>
                    <p class="info-panel__title"><?= e($notice['Heading']) ?></p>
                    <p><?= content_with_link($notice['Introduction'], 'get in touch', (string) $notice['ButtonLink']) ?></p>
                </div>
            </div>
<?php endif; ?>
        </div>
    </section>
<?php endif; ?>

    <!-- Speaking engagements -->
<?php if ($section = $sections['speaking'] ?? null):
    $notice = $sections['speaking-notice'] ?? null;
?>
    <section class="section section--peach"<?= content_labelled($section, 'speaking-title') ?>>
        <?= edit_block('about', 'speaking') ?>
        <div class="container split">
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'speaking-title') ?>
                <?= content_paragraphs($section['Introduction'], 'lead') ?>
                <?= content_paragraphs($section['Body']) ?>
                <ul class="check-list">
<?php foreach (content_lines($about['SpeakingTopics'] ?? '') as $topic): ?>
                    <li><?= icon('check') ?><span><?= e($topic) ?></span></li>
<?php endforeach; ?>
                </ul>
<?php if ($engagements): ?>
                <ul class="check-list">
<?php foreach ($engagements as $event):
    $detail = array_filter([$event['Venue'] ?? '', $event['City'] ?? '', !empty($event['EventDate']) ? date('j F Y', strtotime($event['EventDate'])) : '']);
?>
                    <li><?= icon('check') ?><span><strong><?= e($event['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
                </ul>
<?php elseif ($notice): ?>
                <p class="muted"><?= e($notice['Introduction']) ?></p>
<?php endif; ?>
                <div class="btn-group">
                    <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--dark') ?>
                    <?= content_link($section['SecondaryButtonText'], $section['SecondaryButtonLink'], 'btn btn--outline') ?>
                </div>
            </div>
            <div class="split__media">
                <?= content_frame('art-frame art-frame--gold', $section['Image'] ?? '', $section['ImageAlt'] ?? '', icon('mic', 'icon art-frame__icon')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Call-to-action -->
<?php if ($section = $sections['closing'] ?? null): ?>
    <section class="section cta-banner"<?= content_labelled($section, 'about-cta-title') ?>>
        <?= edit_block('about', 'closing') ?>
        <div class="container cta-banner__inner">
            <?= content_heading($section, 'about-cta-title') ?>
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
