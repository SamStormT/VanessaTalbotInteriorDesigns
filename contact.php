<?php
/**
 * Contact page. Wording comes from the CMS and contact details from Website
 * Settings. The form is interface only for now: nothing submitted is stored
 * or sent, and the page says so.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

$sections = site_sections('contact');
$header   = $sections['page-header'] ?? [];
$settings = site_settings();
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

// Enquire buttons link here with ?service=slug. Values match ContactMessages.EnquiryType.
$serviceToType = [
    'biophilic-design'     => 'Biophilic Design',
    'colour-psychology'    => 'Colour Psychology',
    'photography-services' => 'Photography',
    'workshops'            => 'Workshop',
    'speaking-engagements' => 'Speaking Engagement',
    'consultations'        => 'Consultation',
];
$enquiryTypes = [
    'General'             => 'General enquiry',
    'Biophilic Design'    => 'Biophilic Design',
    'Colour Psychology'   => 'Colour & Design Psychology',
    'Photography'         => 'Photography',
    'Workshop'            => 'Workshop',
    'Speaking Engagement' => 'Speaking Engagement',
    'Consultation'        => 'Consultation',
    'Collaboration'       => 'Collaboration',
];
$selected = $serviceToType[is_string($_GET['service'] ?? null) ? $_GET['service'] : ''] ?? 'General';

$phone = trim((string) ($settings['Phone'] ?? ''));
$email = trim((string) ($settings['Email'] ?? ''));
$place = array_filter([$settings['City'] ?? '', $settings['Province'] ?? '', $settings['Country'] ?? '']);

// The street address is private unless ShowAddress is switched on.
$street = !empty($settings['ShowAddress'])
    ? array_filter([$settings['AddressLine1'] ?? '', $settings['AddressLine2'] ?? ''])
    : [];
$postal = !empty($settings['ShowAddress']) ? trim((string) ($settings['PostalCode'] ?? '')) : '';

$social = array_filter([
    'Facebook'  => $settings['Facebook'] ?? '',
    'Instagram' => $settings['Instagram'] ?? '',
    'LinkedIn'  => $settings['LinkedIn'] ?? '',
    'Pinterest' => $settings['Pinterest'] ?? '',
]);

$formNotice = 'Our online form is not accepting messages yet, so nothing you enter here will be sent. Please phone or email us using the details on this page.';

$pageTitle       = $header['Heading'] ?? '';
$pageDescription = ($header['Introduction'] ?? '') ?: SITE_TAGLINE;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, []) ?>

    <section class="section" aria-label="Get in touch">
        <div class="container split contact-layout">
            <div class="split__content">
<?php if ($section = $sections['details'] ?? null): ?>
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'details-title') ?>
                <?= content_paragraphs($section['Introduction']) ?>
                <ul class="contact-list">
<?php if ($phone !== ''): ?>
                    <li><?= icon('chat') ?><span><strong>Phone</strong> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></span></li>
<?php endif; ?>
<?php if ($email !== ''): ?>
                    <li><?= icon('mail') ?><span><strong>Email</strong> <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></span></li>
<?php endif; ?>
                </ul>
<?php if ($social): ?>
                <ul class="tag-list" aria-label="Social media">
<?php foreach ($social as $network => $link): ?>
                    <li><a href="<?= e(content_url($link)) ?>" rel="noopener"><?= e($network) ?></a></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
<?php endif; ?>

<?php if ($section = $sections['location'] ?? null): ?>
                <div class="contact-location">
                    <?= content_eyebrow($section) ?>
                    <?= content_heading($section, 'location-title') ?>
                    <?= content_paragraphs($section['Introduction']) ?>
<?php if ($street || $place): ?>
                    <address>
<?php foreach ($street as $line): ?>
                        <?= e($line) ?><br>
<?php endforeach; ?>
                        <?= e(implode(', ', $place)) ?><?= $postal !== '' ? ' ' . e($postal) : '' ?>
                    </address>
<?php endif; ?>
                </div>
<?php endif; ?>
            </div>

<?php if ($section = $sections['form'] ?? null): ?>
            <div class="contact-form-wrap">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'form-title') ?>
                <?= content_paragraphs($section['Introduction']) ?>
                <form class="newsletter__form contact-form" action="<?= e(url('contact.php')) ?>" method="post" data-contact-form novalidate>
                    <div class="form-field">
                        <label for="contact-name">First name <span class="required" aria-hidden="true">*</span></label>
                        <input id="contact-name" name="name" type="text" autocomplete="given-name" required>
                    </div>
                    <div class="form-field">
                        <label for="contact-surname">Surname</label>
                        <input id="contact-surname" name="surname" type="text" autocomplete="family-name">
                    </div>
                    <div class="form-field">
                        <label for="contact-email">Email address <span class="required" aria-hidden="true">*</span></label>
                        <input id="contact-email" name="email" type="email" autocomplete="email" required>
                    </div>
                    <div class="form-field">
                        <label for="contact-phone">Phone</label>
                        <input id="contact-phone" name="phone" type="tel" autocomplete="tel">
                    </div>
                    <div class="form-field">
                        <label for="contact-type">What is your enquiry about?</label>
                        <select id="contact-type" name="enquiry_type">
<?php foreach ($enquiryTypes as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $value === $selected ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="contact-subject">Subject</label>
                        <input id="contact-subject" name="subject" type="text">
                    </div>
                    <div class="form-field">
                        <label for="contact-message">Message <span class="required" aria-hidden="true">*</span></label>
                        <textarea id="contact-message" name="message" rows="5" required></textarea>
                    </div>
                    <label class="form-check"><input type="checkbox" name="newsletter" value="1"> Subscribe to Newsletter</label>
                    <button class="btn btn--primary" type="submit">Send message</button>
                    <p class="form-status" role="status" aria-live="polite" data-notice="<?= e($formNotice) ?>"><?= $posted ? e($formNotice) : '' ?></p>
                    <p class="form-note"><?= e($formNotice) ?> See our <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.</p>
                </form>
            </div>
<?php endif; ?>
        </div>
    </section>

<?php if ($section = $sections['faq'] ?? null): ?>
    <section class="section section--peach"<?= content_labelled($section, 'faq-title') ?>>
        <div class="container">
            <?= content_section_header($section, 'faq-title') ?>
            <div class="faq">
<?php foreach ($section['items'] as $item): ?>
                <details class="faq__item">
                    <summary><?= e($item['Title']) ?></summary>
                    <?= content_paragraphs($item['Text']) ?>
                </details>
<?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
