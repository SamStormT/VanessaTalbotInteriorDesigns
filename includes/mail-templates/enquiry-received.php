<?php
/**
 * To a visitor: we received your message or consultation request.
 *
 * Their own message is deliberately not repeated: otherwise someone could
 * type an advert, enter a stranger's email address and use the website to
 * send it. For a consultation, only the choices made on the form are shown
 * (type of space, service, area, dates), so they can check them.
 *
 * @var string $name         First name
 * @var bool   $consultation A consultation request (book-consultation.php)
 * @var array  $choices      Consultation only: label => value
 * @var string $phone        The business phone, if shown on the website
 * @var string $email        The business email, if shown on the website
 * @var string $siteUrl      The website
 */

$heading = $consultation ? 'Your consultation request has arrived' : 'Thank you for your message';
$next    = $consultation
    ? 'You will hear back soon to confirm a date and time, and where to meet. The times you chose are preferences, not yet a booking.'
    : 'You will hear back soon.';
$contact = array_filter([$phone !== '' ? 'Phone: ' . $phone : '', $email !== '' ? 'Email: ' . $email : '']);

return [
    'subject' => $consultation ? 'We received your consultation request' : 'We received your message',
    'heading' => $heading,
    'html'    => '<p style="margin:0 0 16px">Hello ' . e($name) . ',</p>'
               . '<p style="margin:0 0 16px">Thank you for getting in touch with ' . e(SITE_NAME) . '. ' . e($next) . '</p>'
               . ($consultation && $choices ? mail_details($choices) : '')
               . ($contact ? '<p style="margin:0 0 16px">If you need anything in the meantime: ' . e(implode(' · ', $contact)) . '</p>' : '')
               . mail_button($siteUrl, 'Visit the website'),
    'text'    => "Hello $name,\n\nThank you for getting in touch with " . SITE_NAME . ". $next\n\n"
               . ($consultation && $choices ? mail_details_text($choices) . "\n\n" : '')
               . ($contact ? 'If you need anything in the meantime: ' . implode(' | ', $contact) . "\n\n" : '')
               . $siteUrl,
    'footer'  => 'You received this because this email address was entered on a form on our website. If that wasn\'t you, you can ignore it: nothing else will be sent.',
];
