<?php
/**
 * Collaborators. All wording comes from the CMS.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections      = site_sections('portfolio-collaborators');
$header        = $sections['page-header'] ?? [];
$collaborators = site_collaborators();

[$pageTitle, $pageDescription] = content_seo($sections, (string) ($header['Heading'] ?? ''), (string) ($header['Introduction'] ?? ''));

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, [['Portfolio', 'portfolio.php']], edit_block('portfolio-collaborators', 'page-header')) ?>

<?= hidden_block('portfolio-collaborators', 'intro', $sections) ?>
<?php if ($section = $sections['intro'] ?? null): ?>
    <section class="section"<?= content_labelled($section, 'intro-title') ?>>
        <?= edit_block('portfolio-collaborators', 'intro') ?>
        <div class="container">
            <?= content_section_header($section, 'intro-title') ?>
            <div class="content-narrow"><?= content_paragraphs($section['Body'], '', ed_section($section, 'Body')) ?></div>
        </div>
    </section>
<?php endif; ?>

    <section class="section section--peach" aria-label="Collaborators">
        <?= edit_block('portfolio-collaborators', 'list') ?>
        <div class="container">
<?php if ($collaborators): ?>
            <ul class="card-grid card-grid--3">
<?php foreach ($collaborators as $person): ?>
                <li class="card">
<?php if (!empty($person['Logo'])): ?>
                    <img class="collaborator-logo"<?= ed_record('collaborators', 'admin/collaborator-edit.php', (int) ($person['CollaboratorID'] ?? 0), 'Logo', 'collaborators.edit') ?> src="<?= e(image_url((string) $person['Logo'], 'thumb')) ?>" alt="<?= e($person['LogoAlt'] ?? '') ?>" loading="lazy">
<?php endif; ?>
                    <h2 class="card__title"<?= ed_record('collaborators', 'admin/collaborator-edit.php', (int) ($person['CollaboratorID'] ?? 0), 'Name', 'collaborators.edit') ?>><?= e($person['Name']) ?></h2>
<?php if (!empty($person['RoleTitle'])): ?>
                    <p class="card__meta"<?= ed_record('collaborators', 'admin/collaborator-edit.php', (int) ($person['CollaboratorID'] ?? 0), 'RoleTitle', 'collaborators.edit') ?>><?= e($person['RoleTitle']) ?></p>
<?php endif; ?>
                    <p<?= ed_record('collaborators', 'admin/collaborator-edit.php', (int) ($person['CollaboratorID'] ?? 0), 'Description', 'collaborators.edit') ?>><?= e($person['Description'] ?? '') ?></p>
<?php if (!empty($person['WebsiteLink'])): ?>
                    <a class="text-link" href="<?= e(content_url($person['WebsiteLink'])) ?>" rel="noopener"<?= ed_record('collaborators', 'admin/collaborator-edit.php', (int) ($person['CollaboratorID'] ?? 0), 'WebsiteLink', 'collaborators.edit') ?>>Visit website</a>
<?php endif; ?>
                </li>
<?php endforeach; ?>
            </ul>
<?php elseif ($empty = $sections['empty'] ?? null): ?>
            <?= content_empty_state($empty) ?>
<?php endif; ?>
        </div>
    </section>

    <?= isset($sections['closing']) ? content_closing($sections['closing'], 'collaborators-cta-title', edit_block('portfolio-collaborators', 'closing')) : hidden_block('portfolio-collaborators', 'closing', $sections) ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
