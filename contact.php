<?php
/**
 * Contact page. Wording comes from the CMS and contact details from Website
 * Settings. The form saves each message to ContactMessages and emails a
 * notification (see includes/enquiries.php).
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/enquiries.php';

$sections = site_sections('contact');
$header   = $sections['page-header'] ?? [];
$settings = site_settings();

$enquiryTypes = enquiry_types();
$values       = ['enquiry_type' => enquiry_type_for_service(is_string($_GET['service'] ?? null) ? $_GET['service'] : '')];
$errors       = [];
$status       = null; // ['type' => 'success' | 'error', 'text' => ...]
$contactLine  = 'Please try again later, or phone or email us using the details on this page.';
$thanks       = 'Thank you, your message has been sent. We will be in touch soon.';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Redisplay what was typed if the form needs correcting.
    foreach (['name', 'surname', 'email', 'phone', 'subject', 'message', 'enquiry_type', 'newsletter'] as $key) {
        $values[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
    }

    if (!empty($_POST['vt_check'])) {
        // Hidden field that only bots fill in: answer as if it worked, save
        // nothing, and note it so a real person caught by it can be spotted.
        error_log('Contact message ignored: spam-trap field was filled in (IP ' . client_ip() . ')');
        flash_set('success', $thanks);
        header('Location: ' . url('contact.php') . '#contact-form', true, 303);
        exit;
    }

    if (!csrf_valid()) {
        $status = ['type' => 'error', 'text' => 'This form expired for security reasons. Please check your details and send it again.'];
    } else {
        [$data, $errors] = contact_validate($_POST);

        if ($errors !== []) {
            $status = ['type' => 'error', 'text' => 'Please check the highlighted fields.'];
        } else {
            try {
                if (contact_rate_limited(client_ip())) {
                    $status = ['type' => 'error', 'text' => 'We have received several messages from your connection in the last hour. ' . $contactLine];
                } else {
                    contact_submit($data);
                    flash_set('success', $thanks . ($data['SubscribeToNewsletter']
                        ? ' If you are new to the newsletter, please check your inbox for an email to confirm your subscription.'
                        : ''));
                    header('Location: ' . url('contact.php') . '#contact-form', true, 303);
                    exit;
                }
            } catch (Throwable $error) {
                error_log('Contact form message could not be saved: ' . $error->getMessage());
                $status = ['type' => 'error', 'text' => 'Sorry, your message could not be sent. ' . $contactLine];
            }
        }
    }
} elseif ($flash = flash_take()) {
    $status = ['type' => $flash['type'], 'text' => $flash['message']];
}

/** Attributes for a field that may have an error, and the error itself. */
$invalid = static fn(string $name): string => isset($errors[$name])
    ? ' aria-invalid="true" aria-describedby="contact-' . $name . '-error"' : '';
$errorText = static fn(string $name): string => isset($errors[$name])
    ? '<p class="form-error" id="contact-' . $name . '-error">' . e($errors[$name]) . '</p>' : '';
$value = static fn(string $name): string => e((string) ($values[$name] ?? ''));

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
            <div class="contact-form-wrap" id="contact-form">
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'form-title') ?>
                <?= content_paragraphs($section['Introduction']) ?>
                <form class="newsletter__form contact-form" action="<?= e(url('contact.php')) ?>#contact-form" method="post" data-contact-form novalidate>
                    <?= csrf_field() ?>
<?php if ($status !== null): ?>
                    <p class="form-alert form-alert--<?= e($status['type']) ?>" role="<?= $status['type'] === 'error' ? 'alert' : 'status' ?>" tabindex="-1" data-form-alert><?= e($status['text']) ?></p>
<?php endif; ?>
                    <div class="form-field">
                        <label for="contact-name">First name <span class="required" aria-hidden="true">*</span></label>
                        <input id="contact-name" name="name" type="text" autocomplete="given-name" maxlength="100" required value="<?= $value('name') ?>"<?= $invalid('name') ?>>
                        <?= $errorText('name') ?>
                    </div>
                    <div class="form-field">
                        <label for="contact-surname">Surname</label>
                        <input id="contact-surname" name="surname" type="text" autocomplete="family-name" maxlength="100" value="<?= $value('surname') ?>"<?= $invalid('surname') ?>>
                        <?= $errorText('surname') ?>
                    </div>
                    <div class="form-field">
                        <label for="contact-email">Email address <span class="required" aria-hidden="true">*</span></label>
                        <input id="contact-email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= $value('email') ?>"<?= $invalid('email') ?>>
                        <?= $errorText('email') ?>
                    </div>
                    <div class="form-field">
                        <label for="contact-phone">Phone</label>
                        <input id="contact-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= $value('phone') ?>"<?= $invalid('phone') ?>>
                        <?= $errorText('phone') ?>
                    </div>
                    <div class="form-field">
                        <label for="contact-type">What is your enquiry about?</label>
                        <select id="contact-type" name="enquiry_type">
<?php foreach ($enquiryTypes as $type => $label): ?>
                            <option value="<?= e($type) ?>"<?= $type === ($values['enquiry_type'] ?? '') ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="contact-subject">Subject</label>
                        <input id="contact-subject" name="subject" type="text" maxlength="255" value="<?= $value('subject') ?>"<?= $invalid('subject') ?>>
                        <?= $errorText('subject') ?>
                    </div>
                    <div class="form-field">
                        <label for="contact-message">Message <span class="required" aria-hidden="true">*</span></label>
                        <textarea id="contact-message" name="message" rows="5" maxlength="<?= CONTACT_MESSAGE_MAX ?>" required<?= $invalid('message') ?>><?= $value('message') ?></textarea>
                        <?= $errorText('message') ?>
                    </div>
                    <div class="form-trap" aria-hidden="true">
                        <label for="contact-vt-check">Leave this blank</label>
                        <input id="contact-vt-check" name="vt_check" type="text" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore>
                    </div>
                    <label class="form-check"><input type="checkbox" name="newsletter" value="1"<?= !empty($values['newsletter']) ? ' checked' : '' ?>> Subscribe to Newsletter</label>
                    <button class="btn btn--primary" type="submit">Send message</button>
                    <p class="form-status" role="status" aria-live="polite"></p>
                    <p class="form-note">We use your details only to reply to your enquiry, and to send the newsletter if you ask for it. See our <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.</p>
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
