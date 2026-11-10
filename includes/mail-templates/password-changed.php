<?php
/**
 * To an admin user: confirmation that their password was changed.
 *
 * @var string $name     First name
 * @var string $loginUrl Sign-in page
 */

return [
    'subject' => 'Your password was changed',
    'heading' => 'Your password was changed',
    'html'    => '<p style="margin:0 0 16px">Hello ' . e($name) . ',</p>'
               . '<p style="margin:0 0 16px">The password for your account on the ' . e(SITE_NAME) . ' website was just changed, and you were signed out everywhere else.</p>'
               . mail_button($loginUrl, 'Sign in')
               . '<p style="margin:0 0 16px">If you didn\'t do this, contact the website administrator straight away.</p>',
    'text'    => "Hello $name,\n\nThe password for your account on the " . SITE_NAME . " website was just changed, and you were signed out everywhere else.\n\n"
               . "Sign in: $loginUrl\n\nIf you didn't do this, contact the website administrator straight away.",
];
