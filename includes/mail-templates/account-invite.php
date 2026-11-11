<?php
/**
 * To a new admin user: their account exists; choose a password to start.
 *
 * @var string $name      First name
 * @var string $resetUrl  Single-use link to choose a password
 * @var int    $minutes   How long the link works
 * @var string $role      Their role
 * @var string $invitedBy Who created the account
 * @var string $loginUrl  Sign-in page
 */

$days = (int) round($minutes / 1440);

return [
    'subject' => 'Your account for the ' . SITE_NAME . ' website',
    'heading' => 'Welcome to the website admin',
    'html'    => '<p style="margin:0 0 16px">Hello ' . e($name) . ',</p>'
               . '<p style="margin:0 0 16px">' . e($invitedBy) . ' has created an account for you to help manage the ' . e(SITE_NAME) . ' website, with the role <strong>' . e($role) . '</strong>.</p>'
               . '<p style="margin:0 0 16px">To start, choose your password:</p>'
               . mail_button($resetUrl, 'Choose my password')
               . '<p style="margin:0 0 16px">The link works once and expires in ' . $days . ' days. If the button doesn\'t work, copy this address into your browser:<br>'
               . '<span style="word-break:break-all;color:#7A6230">' . e($resetUrl) . '</span></p>'
               . '<p style="margin:0 0 16px">After that, sign in at <a href="' . e($loginUrl) . '" style="color:#7A6230">' . e($loginUrl) . '</a> with this email address.</p>',
    'text'    => "Hello $name,\n\n$invitedBy has created an account for you to help manage the " . SITE_NAME . " website, with the role $role.\n\n"
               . "Choose your password here (the link works once and expires in $days days):\n$resetUrl\n\n"
               . "After that, sign in at $loginUrl with this email address.",
];
