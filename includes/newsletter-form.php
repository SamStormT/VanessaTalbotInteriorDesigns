<?php
/**
 * The newsletter sign-up form, shared by the home page and the blog.
 *
 * $source is 'home' or 'blog' (stored as the subscriber's Source), and
 * $return is where the page goes back to when JavaScript is off.
 * $note is the privacy line under the form (CMS wording).
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/newsletter.php';

function newsletter_form(string $idPrefix, string $source, string $return, ?string $note): string
{
    $result = is_string($_GET['newsletter'] ?? null) ? $_GET['newsletter'] : '';
    $id     = static fn(string $name): string => e($idPrefix . '-' . $name);

    return '<form class="newsletter__form" action="' . e(url('newsletter.php')) . '" method="post" data-newsletter-form novalidate>'
         . '<input type="hidden" name="source" value="' . e($source) . '">'
         . '<input type="hidden" name="return" value="' . e($return) . '">'
         . '<div class="form-field"><label for="' . $id('name') . '">First name</label>'
         . '<input id="' . $id('name') . '" name="name" type="text" autocomplete="given-name" maxlength="100"></div>'
         . '<div class="form-field"><label for="' . $id('email') . '">Email address <span class="required" aria-hidden="true">*</span></label>'
         . '<input id="' . $id('email') . '" name="email" type="email" autocomplete="email" maxlength="254" required aria-describedby="' . $id('status') . '"'
         . ($result === 'invalid' ? ' aria-invalid="true"' : '') . '></div>'
         . '<div class="form-trap" aria-hidden="true"><label for="' . $id('vt-check') . '">Leave this blank</label>'
         . '<input id="' . $id('vt-check') . '" name="vt_check" type="text" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore></div>'
         . '<button class="btn btn--primary" type="submit">' . icon('mail', 'icon icon--inline') . ' Subscribe</button>'
         . '<p id="' . $id('status') . '" class="form-status" role="status" aria-live="polite">' . e(newsletter_messages()[$result] ?? '') . '</p>'
         . ($note !== null && trim($note) !== '' ? '<p class="form-note">' . content_with_link($note, 'Privacy Policy', 'privacy-policy.php') . '</p>' : '')
         . '</form>';
}
