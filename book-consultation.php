<?php
/**
 * Book a consultation: the type of space, the service, the visitor's area
 * (suburb or town) and two preferred dates. Where to meet is agreed when the
 * time is confirmed. Saved as a Contact Message of type Consultation (so it is
 * in the same admin list and notification email as other enquiries), with
 * the same spam trap, security token and hourly limit as the contact form.
 * Wording comes from the CMS under the page key "consultation".
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/enquiries.php';

$sections = site_sections('consultation');
$header   = $sections['page-header'] ?? [];
$settings = site_settings();

$services = [];
foreach (site_services() as $service) {
    $services[(string) $service['Slug']] = (string) $service['ServiceName'];
}

$values      = ['service' => is_string($_GET['service'] ?? null) && isset($services[$_GET['service']]) ? $_GET['service'] : '', 'time1' => 'any', 'time2' => 'any'];
$errors      = [];
$status      = null;
$contactLine = 'Please try again later, or phone or email using the details on this page.';
$thanks      = 'Thank you, your request has been sent. You will hear back soon to confirm a time.';
$self        = url('book-consultation.php') . '#booking-form';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (['name', 'surname', 'email', 'phone', 'space', 'service', 'area', 'date1', 'time1', 'date2', 'time2', 'notes', 'newsletter'] as $key) {
        $values[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
    }

    if (!empty($_POST['vt_check'])) {
        error_log('Consultation request ignored: spam-trap field was filled in (IP ' . client_ip() . ')');
        flash_set('success', $thanks);
        header('Location: ' . $self, true, 303);
        exit;
    }

    if (!csrf_valid()) {
        $status = ['type' => 'error', 'text' => 'This form expired for security reasons. Please check your details and send it again.'];
    } else {
        [$data, $errors] = consultation_validate($_POST, $services);
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
                    header('Location: ' . $self, true, 303);
                    exit;
                }
            } catch (Throwable $error) {
                error_log('Consultation request could not be saved: ' . $error->getMessage());
                $status = ['type' => 'error', 'text' => 'Sorry, your request could not be sent. ' . $contactLine];
            }
        }
    }
} elseif ($flash = flash_take()) {
    $status = ['type' => $flash['type'], 'text' => $flash['message']];
}

$invalid = static fn(string $name): string => isset($errors[$name])
    ? ' aria-invalid="true" aria-describedby="booking-' . $name . '-error"' : '';
$errorText = static fn(string $name): string => isset($errors[$name])
    ? '<p class="form-error" id="booking-' . $name . '-error">' . e($errors[$name]) . '</p>' : '';
$value = static fn(string $name): string => e((string) ($values[$name] ?? ''));
$minDate = date('Y-m-d', strtotime('tomorrow'));
$maxDate = date('Y-m-d', strtotime('+1 year'));

$phone = trim((string) ($settings['Phone'] ?? ''));
$email = trim((string) ($settings['Email'] ?? ''));

[$pageTitle, $pageDescription] = content_seo($sections, (string) ($header['Heading'] ?? 'Book a consultation'), (string) ($header['Introduction'] ?? ''));

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, [['Services', 'services.php']], edit_block('consultation', 'page-header')) ?>

    <section class="section" aria-label="Booking">
        <div class="container split booking-layout">
<?= hidden_block('consultation', 'form', $sections) ?>
<?php if ($section = $sections['form'] ?? null): ?>
            <div class="contact-form-wrap booking-form-wrap" id="booking-form">
                <?= edit_block('consultation', 'form') ?>
                <?= content_eyebrow($section) ?>
                <?= content_heading($section, 'form-title') ?>
                <?= content_paragraphs($section['Introduction'], '', ed_section($section, 'Introduction')) ?>
                <form class="newsletter__form contact-form booking-form" action="<?= e($self) ?>" method="post" data-contact-form novalidate>
                    <?= csrf_field() ?>
<?php if ($status !== null): ?>
                    <p class="form-alert form-alert--<?= e($status['type']) ?>" role="<?= $status['type'] === 'error' ? 'alert' : 'status' ?>" tabindex="-1" data-form-alert><?= e($status['text']) ?></p>
<?php endif; ?>
                    <fieldset class="booking-group">
                        <legend>About you</legend>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="booking-name">First name <span class="required" aria-hidden="true">*</span></label>
                                <input id="booking-name" name="name" type="text" autocomplete="given-name" maxlength="100" required value="<?= $value('name') ?>"<?= $invalid('name') ?>>
                                <?= $errorText('name') ?>
                            </div>
                            <div class="form-field">
                                <label for="booking-surname">Surname</label>
                                <input id="booking-surname" name="surname" type="text" autocomplete="family-name" maxlength="100" value="<?= $value('surname') ?>"<?= $invalid('surname') ?>>
                                <?= $errorText('surname') ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="booking-email">Email address <span class="required" aria-hidden="true">*</span></label>
                                <input id="booking-email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= $value('email') ?>"<?= $invalid('email') ?>>
                                <?= $errorText('email') ?>
                            </div>
                            <div class="form-field">
                                <label for="booking-phone">Phone</label>
                                <input id="booking-phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= $value('phone') ?>"<?= $invalid('phone') ?>>
                                <?= $errorText('phone') ?>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="booking-group">
                        <legend>Your space</legend>
                        <div class="form-field">
                            <label for="booking-space">Type of space <span class="required" aria-hidden="true">*</span></label>
                            <select id="booking-space" name="space" required<?= $invalid('space') ?>>
                                <option value="">Choose…</option>
<?php foreach (consultation_spaces() as $space): ?>
                                <option<?= $space === ($values['space'] ?? '') ? ' selected' : '' ?>><?= e($space) ?></option>
<?php endforeach; ?>
                            </select>
                            <?= $errorText('space') ?>
                        </div>
                        <div class="form-field">
                            <label for="booking-service">Service you're interested in</label>
                            <select id="booking-service" name="service"<?= $invalid('service') ?>>
                                <option value="">Not sure yet</option>
<?php foreach ($services as $slug => $name): ?>
                                <option value="<?= e($slug) ?>"<?= $slug === ($values['service'] ?? '') ? ' selected' : '' ?>><?= e($name) ?></option>
<?php endforeach; ?>
                            </select>
                            <?= $errorText('service') ?>
                        </div>
                        <div class="form-field">
                            <label for="booking-notes">Tell me about the space and what you'd like to change</label>
                            <textarea id="booking-notes" name="notes" rows="4" maxlength="3000"<?= $invalid('notes') ?>><?= $value('notes') ?></textarea>
                            <?= $errorText('notes') ?>
                        </div>
                    </fieldset>

                    <fieldset class="booking-group">
                        <legend>Where and when</legend>
                        <div class="form-field">
                            <label for="booking-area">Your area (suburb or town) <span class="required" aria-hidden="true">*</span></label>
                            <input id="booking-area" name="area" type="text" autocomplete="address-level2" maxlength="150" required value="<?= $value('area') ?>"<?= $invalid('area') ?>>
                            <?= $errorText('area') ?>
                        </div>
<?php foreach ([1 => 'First choice', 2 => 'Second choice (optional)'] as $n => $label): ?>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="booking-date<?= $n ?>"><?= e($label) ?><?= $n === 1 ? ' <span class="required" aria-hidden="true">*</span>' : '' ?></label>
                                <input id="booking-date<?= $n ?>" name="date<?= $n ?>" type="date" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= $value('date' . $n) ?>"<?= $n === 1 ? ' required' : '' ?><?= $invalid('date' . $n) ?>>
                                <?= $errorText('date' . $n) ?>
                            </div>
                            <div class="form-field">
                                <label for="booking-time<?= $n ?>">Time of day</label>
                                <select id="booking-time<?= $n ?>" name="time<?= $n ?>">
<?php foreach (consultation_times() as $key => $time): ?>
                                    <option value="<?= e($key) ?>"<?= $key === ($values['time' . $n] ?? '') ? ' selected' : '' ?>><?= e($time) ?></option>
<?php endforeach; ?>
                                </select>
                            </div>
                        </div>
<?php endforeach; ?>
                    </fieldset>

                    <div class="form-trap" aria-hidden="true">
                        <label for="booking-vt-check">Leave this blank</label>
                        <input id="booking-vt-check" name="vt_check" type="text" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore>
                    </div>
                    <label class="form-check"><input type="checkbox" name="newsletter" value="1"<?= !empty($values['newsletter']) ? ' checked' : '' ?>> Subscribe to Newsletter</label>
                    <button class="btn btn--primary" type="submit">Request a consultation</button>
                    <p class="form-status" role="status" aria-live="polite"></p>
                    <p class="form-note">These are preferred times, not a confirmed booking. We use your details only to arrange the consultation, and to send the newsletter if you ask for it. See our <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.</p>
                </form>
            </div>
<?php endif; ?>

            <aside class="booking-aside">
<?= hidden_block('consultation', 'next', $sections) ?>
<?php if ($section = $sections['next'] ?? null): ?>
                <div class="booking-next">
                    <?= edit_block('consultation', 'next') ?>
                    <?= content_heading($section, 'next-title') ?>
                    <ol class="booking-steps">
<?php foreach ($section['items'] as $item): ?>
                        <li>
                            <span class="booking-steps__icon"><?= icon((string) ($item['Icon'] ?: 'leaf')) ?></span>
                            <span><strong<?= ed_item($item, 'Title') ?>><?= e($item['Title']) ?></strong>
                            <span<?= ed_item($item, 'Text') ?>><?= e($item['Text']) ?></span></span>
                        </li>
<?php endforeach; ?>
                    </ol>
                </div>
<?php endif; ?>
<?php if ($phone !== '' || $email !== ''): ?>
                <div class="booking-contact">
                    <h2 class="subheading">Rather talk first?</h2>
                    <ul class="contact-list">
<?php if ($phone !== ''): ?>
                        <li<?= ed_setting('Phone') ?>><?= icon('chat') ?><span><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a></span></li>
<?php endif; ?>
<?php if ($email !== ''): ?>
                        <li<?= ed_setting('Email') ?>><?= icon('mail') ?><span><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></span></li>
<?php endif; ?>
                    </ul>
                </div>
<?php endif; ?>
            </aside>
        </div>
    </section>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
