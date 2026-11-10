<?php
/**
 * To a new subscriber: confirm the sign-up (double opt-in, for POPIA).
 *
 * @var string $name           First name, or ''
 * @var string $confirmUrl     Confirmation link
 * @var string $unsubscribeUrl Opt-out link
 */

$greeting = $name !== '' ? 'Hello ' . $name . ',' : 'Hello,';

return [
    'subject' => 'Please confirm your newsletter subscription',
    'heading' => 'Please confirm your subscription',
    'html'    => '<p style="margin:0 0 16px">' . e($greeting) . '</p>'
               . '<p style="margin:0 0 16px">Thank you for signing up to the ' . e(SITE_NAME) . ' newsletter. Please confirm that this is your email address, and that you would like to receive it.</p>'
               . mail_button($confirmUrl, 'Confirm my subscription')
               . '<p style="margin:0 0 16px">If you didn\'t sign up, ignore this email and you won\'t hear from us.</p>',
    'text'    => "$greeting\n\nThank you for signing up to the " . SITE_NAME . " newsletter. Please confirm that this is your email address, and that you would like to receive it:\n$confirmUrl\n\n"
               . "If you didn't sign up, ignore this email and you won't hear from us.",
    'footer'      => 'Changed your mind? <a href="' . e($unsubscribeUrl) . '" style="color:#7A6230">Unsubscribe</a>.',
    'footer_text' => "Changed your mind? Unsubscribe: $unsubscribeUrl",
];
