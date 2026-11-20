<?php
/**
 * Collaborators. All wording comes from the CMS.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/countries.php';

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
            <ul class="collab-grid">
<?php foreach ($collaborators as $person):
    $id      = (int) ($person['CollaboratorID'] ?? 0);
    $ed      = static fn(string $field): string => ed_record('collaborators', 'admin/collaborator-edit.php', $id, $field, 'collaborators.edit');
    $country = country_names()[(string) ($person['Country'] ?? '')] ?? '';
    $flag    = country_flag_url($person['Country'] ?? '');
    $phone   = trim((string) ($person['Phone'] ?? ''));
?>
                <li class="collab-card<?= $flag !== '' ? ' collab-card--flag' : '' ?>">
<?php if ($flag !== ''): ?>
                    <img class="collab-card__flag" src="<?= e($flag) ?>" alt="" width="112" height="112"<?= $ed('Country') ?>>
<?php endif; ?>
<?php if ($country !== ''): ?>
                    <p class="collab-card__country"<?= $ed('Country') ?>><?= e($country) ?></p>
<?php endif; ?>
<?php if (!empty($person['Logo'])): ?>
                    <div class="collab-card__picture<?= ($person['ImageFit'] ?? '') === 'Logo' ? ' collab-card__picture--logo' : '' ?>"<?= $ed('Logo') ?>>
                        <img src="<?= e(image_url((string) $person['Logo'], 'web')) ?>"<?= image_srcset((string) $person['Logo'], '(max-width: 700px) 100vw, (max-width: 1100px) 50vw, 400px') ?> alt="<?= e($person['LogoAlt'] ?? '') ?>" loading="lazy">
                    </div>
<?php endif; ?>
                    <h2 class="collab-card__name"<?= $ed('Name') ?>><?= icon('person') ?><span><?= e($person['Name']) ?></span></h2>
                    <ul class="collab-card__details">
<?php if (!empty($person['RoleTitle'])): ?>
                        <li<?= $ed('RoleTitle') ?>><?= icon('brush') ?><span><?= e($person['RoleTitle']) ?></span></li>
<?php endif; ?>
<?php if (!empty($person['Email'])): ?>
<?php [$mailName, $mailDomain] = array_pad(explode('@', (string) $person['Email'], 2), 2, ''); ?>
                        <li<?= $ed('Email') ?>><?= icon('mail') ?><span class="protected-email" data-name="<?= e(strrev($mailName)) ?>" data-domain="<?= e(strrev($mailDomain)) ?>"><?= e($mailName) ?> (at) <?= e($mailDomain) ?></span></li>
<?php endif; ?>
<?php if ($phone !== ''): ?>
                        <li<?= $ed('Phone') ?>><?= icon('phone') ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></li>
<?php endif; ?>
                    </ul>
<?php if (!empty($person['Description'])): ?>
                    <p class="collab-card__about"<?= $ed('Description') ?>><?= e($person['Description']) ?></p>
<?php endif; ?>
<?php if (!empty($person['WebsiteLink'])): ?>
                    <a class="btn btn--outline collab-card__button" href="<?= e(content_url($person['WebsiteLink'])) ?>" rel="noopener" aria-label="Learn more about <?= e($person['Name']) ?>"<?= $ed('WebsiteLink') ?>>Learn more</a>
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
