<?php
/**
 * To an admin user: a link to choose a new password.
 *
 * @var string $name     First name
 * @var string $resetUrl Single-use link containing the raw token
 * @var int    $minutes  How long the link works
 */

return [
    'subject' => 'Reset your password',
    'heading' => 'Reset your password',
    'html'    => '<p style="margin:0 0 16px">Hello ' . e($name) . ',</p>'
               . '<p style="margin:0 0 16px">Someone asked to reset the password for your account on the ' . e(SITE_NAME) . ' website. Use the button below to choose a new one.</p>'
               . mail_button($resetUrl, 'Choose a new password')
               . '<p style="margin:0 0 16px">The link works once and expires in ' . $minutes . ' minutes. If the button doesn\'t work, copy this address into your browser:<br>'
               . '<span style="word-break:break-all;color:#7A6230">' . e($resetUrl) . '</span></p>'
               . '<p style="margin:0 0 16px">If you didn\'t ask for this, you can ignore this email. Your password stays the same.</p>',
    'text'    => "Hello $name,\n\nSomeone asked to reset the password for your account on the " . SITE_NAME . " website.\n\n"
               . "Choose a new password here (the link works once and expires in $minutes minutes):\n$resetUrl\n\n"
               . "If you didn't ask for this, you can ignore this email. Your password stays the same.",
];
