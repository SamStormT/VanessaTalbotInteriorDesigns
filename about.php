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
$memberships    = array_values(array_filter($qualifications, static fn(array $item): bool => ($item['Type'] ?? '') === 'Membership'));
$qualifications = array_values(array_filter($qualifications, static fn(array $item): bool => ($item['Type'] ?? '') !== 'Membership'));
$engagements    = site_engagements();

$pageTitle       = ($about['MetaTitle'] ?? '') ?: ($sections['page-header']['Heading'] ?? '');
$pageDescription = ($about['MetaDescription'] ?? '') ?: SITE_TAGLINE;

$GLOBALS['vt_breadcrumbs'] = [['Home', 'index.php'], ['About', null]]; // for search engines, as shown below
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
            <h1 id="page-title"<?= ed_section($sections['page-header'] ?? null, 'Heading') ?>><?= e($sections['page-header']['Heading'] ?? '') ?></h1>
            <p class="tagline page-hero__tagline"<?= ed_setting('Tagline') ?>><?= tagline_html() ?></p>
        </div>
    </section>

    <!-- Vanessa's story -->
<?= hidden_block('about', 'story', $sections) ?>
<?php if ($section = $sections['story'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'story-title') ?>>
        <?= edit_block('about', 'story') ?>
        <div class="container split">
            <div class="split__media">
                <?= content_frame('art-frame art-frame--portrait art-frame--arch art-frame--peach', $about['PortraitImage'] ?? '', $about['PortraitImageAlt'] ?? '', botanical('botanical art-frame__botanical'), 'web', ed_about('PortraitImage')) ?>
            </div>
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'story-title') ?>
                <?= content_paragraphs($about['Biography'] ?? '', 'lead', ed_about('Biography')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Mission and vision -->
<?= hidden_block('about', 'mission', $sections) ?>
<?php if ($section = $sections['mission'] ?? null): ?>
    <?= content_items_section($section, 'mission-title', false, edit_block('about', 'mission')) ?>
<?php endif; ?>

    <!-- Science to interior design journey -->
<?= hidden_block('about', 'journey', $sections) ?>
<?php if ($section = $sections['journey'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'journey-title') ?>>
        <?= edit_block('about', 'journey') ?>
        <div class="container">
            <?= content_section_header($section, 'journey-title') ?>

            <ol class="journey">
<?php foreach ($section['items'] as $item): ?>
                <li class="journey__step">
                    <span class="journey__icon"<?= ed_item($item, 'Icon') ?>><?= icon((string) $item['Icon']) ?></span>
                    <h3<?= ed_item($item, 'Title') ?>><?= e($item['Title']) ?></h3>
                    <p<?= ed_item($item, 'Text') ?>><?= e($item['Text']) ?></p>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>
<?php endif; ?>

    <!-- Biophilic design philosophy -->
<?= hidden_block('about', 'philosophy', $sections) ?>
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
                        <p<?= ed_item($quote, 'Text') ?>>&ldquo;<?= e($quote['Text']) ?>&rdquo;</p>
                    </blockquote>
                    <figcaption><span class="tagline"<?= ed_item($quote, 'Title') ?>><?= e($quote['Title']) ?></span></figcaption>
                </figure>
<?php endif; ?>
            </div>
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'philosophy-title') ?>
                <?= content_paragraphs($section['Introduction'], 'lead', ed_section($section, 'Introduction')) ?>
                <?= content_paragraphs($section['Body'], '', ed_section($section, 'Body')) ?>
                <ul class="check-list">
<?php foreach ($section['items'] as $item): ?>
                    <li<?= ed_item($item, 'Title') ?>><?= icon('check') ?><span><strong><?= e($item['Title']) ?></strong>: <?= e($item['Text']) ?></span></li>
<?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Values -->
<?= hidden_block('about', 'values', $sections) ?>
<?php if ($section = $sections['values'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'values-title') ?>>
        <?= edit_block('about', 'values') ?>
        <div class="container">
            <?= content_section_header($section, 'values-title', $about['CoreValues'] ?? '', ed_about('CoreValues')) ?>
            <ul class="values-grid">
<?php foreach ($values as $value): ?>
                <li class="value">
                    <span class="value__icon"<?= ed_record('about_values', 'admin/about-value-edit.php', (int) ($value['ValueID'] ?? 0), 'Icon', 'about_items.edit') ?>><?= icon((string) $value['Icon']) ?></span>
                    <h3 class="value__title"<?= ed_record('about_values', 'admin/about-value-edit.php', (int) ($value['ValueID'] ?? 0), 'Title', 'about_items.edit') ?>><?= e($value['Title']) ?></h3>
                    <p<?= ed_record('about_values', 'admin/about-value-edit.php', (int) ($value['ValueID'] ?? 0), 'Description', 'about_items.edit') ?>><?= e($value['Description']) ?></p>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

    <!-- Qualifications -->
<?= hidden_block('about', 'qualifications', $sections) ?>
<?php if ($section = $sections['qualifications'] ?? null):
    $notice = $sections['qualifications-notice'] ?? null;
?>
    <section class="section"<?= content_labelled($section, 'qualifications-title') ?>>
        <?= edit_block('about', 'qualifications') ?>
        <div class="container">
            <?= content_section_header($section, 'qualifications-title', $about['Qualifications'] ?? '', ed_about('Qualifications')) ?>
<?php if ($qualifications || $memberships): ?>
            <div class="credentials">
<?php if ($qualifications): ?>
            <ul class="check-list">
<?php foreach ($qualifications as $item):
    $detail = array_filter([$item['Institution'] ?? '', $item['FieldOfStudy'] ?? '', $item['YearCompleted'] ?? '']);
?>
                <li<?= ed_record('qualifications', 'admin/qualification-edit.php', (int) ($item['QualificationID'] ?? 0), 'Title', 'about_items.edit') ?>><?= icon('check') ?><span><strong><?= e($item['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
<?php if ($memberships): ?>
            <h3 class="subheading">Professional memberships</h3>
            <ul class="check-list">
<?php foreach ($memberships as $item):
    $detail = array_filter([$item['Institution'] ?? '', $item['YearCompleted'] ?? '']);
?>
                <li<?= ed_record('qualifications', 'admin/qualification-edit.php', (int) ($item['QualificationID'] ?? 0), 'Title', 'about_items.edit') ?>><?= icon('group') ?><span><strong><?= e($item['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
            </ul>
<?php endif; ?>
            </div>
<?php elseif ($notice): ?>
            <div class="info-panel">
                <span class="info-panel__icon"><?= icon('shield') ?></span>
                <div>
                    <p class="info-panel__title"<?= ed_section($notice, 'Heading') ?>><?= e($notice['Heading']) ?></p>
                    <p<?= ed_section($notice, 'Introduction') ?>><?= content_with_link($notice['Introduction'], 'get in touch', (string) $notice['ButtonLink']) ?></p>
                </div>
            </div>
<?php endif; ?>
        </div>
    </section>
<?php endif; ?>

    <!-- Speaking engagements -->
<?= hidden_block('about', 'speaking', $sections) ?>
<?php if ($section = $sections['speaking'] ?? null):
    $notice = $sections['speaking-notice'] ?? null;
?>
    <section class="section section--peach"<?= content_labelled($section, 'speaking-title') ?>>
        <?= edit_block('about', 'speaking') ?>
        <div class="container split">
            <div class="split__content">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'speaking-title') ?>
                <?= content_paragraphs($section['Introduction'], 'lead', ed_section($section, 'Introduction')) ?>
                <?= content_paragraphs($section['Body'], '', ed_section($section, 'Body')) ?>
                <ul class="check-list">
<?php foreach (content_lines($about['SpeakingTopics'] ?? '') as $topic): ?>
                    <li<?= ed_about('SpeakingTopics') ?>><?= icon('check') ?><span><?= e($topic) ?></span></li>
<?php endforeach; ?>
                </ul>
<?php if ($engagements): ?>
                <ul class="check-list">
<?php foreach ($engagements as $event):
    $detail = array_filter([$event['Venue'] ?? '', $event['City'] ?? '', !empty($event['EventDate']) ? date('j F Y', strtotime($event['EventDate'])) : '']);
?>
                    <li<?= ed_record('speaking', 'admin/speaking-edit.php', (int) ($event['EngagementID'] ?? 0), 'Title', 'speaking.edit') ?>><?= icon('check') ?><span><strong><?= e($event['Title']) ?></strong><?= $detail ? ', ' . e(implode(', ', $detail)) : '' ?></span></li>
<?php endforeach; ?>
                </ul>
<?php elseif ($notice): ?>
                <p class="muted"<?= ed_section($notice, 'Introduction') ?>><?= e($notice['Introduction']) ?></p>
<?php endif; ?>
                <div class="btn-group">
                    <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--dark', '', ed_section($section, 'ButtonText')) ?>
                    <?= content_link($section['SecondaryButtonText'], $section['SecondaryButtonLink'], 'btn btn--outline', '', ed_section($section, 'SecondaryButtonText')) ?>
                </div>
            </div>
            <div class="split__media">
                <?= content_frame('art-frame art-frame--gold', $section['Image'] ?? '', $section['ImageAlt'] ?? '', icon('mic', 'icon art-frame__icon'), 'web', ed_section($section, 'Image')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

    <!-- Call-to-action -->
<?= hidden_block('about', 'closing', $sections) ?>
<?php if ($section = $sections['closing'] ?? null): ?>
    <section class="section cta-banner"<?= content_labelled($section, 'about-cta-title') ?>>
        <?= edit_block('about', 'closing') ?>
        <div class="container cta-banner__inner">
            <?= content_heading($section, 'about-cta-title') ?>
            <?= content_paragraphs($section['Introduction'], '', ed_section($section, 'Introduction')) ?>
            <div class="btn-group btn-group--centred">
                <?= content_link($section['ButtonText'], $section['ButtonLink'], 'btn btn--primary', '', ed_section($section, 'ButtonText')) ?>
                <?= content_link($section['SecondaryButtonText'], $section['SecondaryButtonLink'], 'btn btn--outline', '', ed_section($section, 'SecondaryButtonText')) ?>
            </div>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
