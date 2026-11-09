<?php
/**
 * Collaborators. All wording comes from the CMS.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections      = site_sections('portfolio-collaborators');
$header        = $sections['page-header'] ?? [];
$collaborators = site_collaborators();

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

    <section class="section section--peach" aria-label="Collaborators">
        <div class="container">
<?php if ($collaborators): ?>
            <ul class="card-grid card-grid--3">
<?php foreach ($collaborators as $person): ?>
                <li class="card">
<?php if (!empty($person['Logo'])): ?>
                    <img class="collaborator-logo" src="<?= e(content_url($person['Logo'])) ?>" alt="<?= e($person['LogoAlt'] ?? '') ?>" loading="lazy">
<?php endif; ?>
                    <h2 class="card__title"><?= e($person['Name']) ?></h2>
<?php if (!empty($person['RoleTitle'])): ?>
                    <p class="card__meta"><?= e($person['RoleTitle']) ?></p>
<?php endif; ?>
                    <p><?= e($person['Description'] ?? '') ?></p>
<?php if (!empty($person['WebsiteLink'])): ?>
                    <a class="text-link" href="<?= e(content_url($person['WebsiteLink'])) ?>" rel="noopener">Visit website</a>
<?php endif; ?>
                </li>
<?php endforeach; ?>
            </ul>
<?php elseif ($empty = $sections['empty'] ?? null): ?>
            <?= content_empty_state($empty) ?>
<?php endif; ?>
        </div>
    </section>

    <?= isset($sections['closing']) ? content_closing($sections['closing'], 'collaborators-cta-title') : '' ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
