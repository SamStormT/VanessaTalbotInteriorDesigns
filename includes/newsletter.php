<?php
/**
 * Newsletter sign-ups: subscribe, confirm by email (double opt-in, which
 * POPIA expects as proof of consent) and unsubscribe.
 *
 * Every confirmation and opt-out link uses the subscriber's UnsubscribeToken,
 * a 64-character random value that only the subscriber's inbox receives.
 * Sending campaigns is a later phase; campaigns will only go to subscribers
 * with SubscriptionStatus = 'Subscribed' and a ConfirmedDate.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mail.php';

const NEWSLETTER_MAX_PER_HOUR   = 10; // sign-ups from one IP address
const NEWSLETTER_RESEND_MINUTES = 10; // gap before a confirmation email is sent again

/** Messages shown to visitors, keyed by result. Also used by the no-JavaScript fallback. */
function newsletter_messages(): array
{
    return [
        'thanks'  => 'Thank you! Please check your inbox for an email to confirm your subscription. If you are already subscribed, there is nothing more to do.',
        'invalid' => 'Please enter a valid email address.',
        'busy'    => 'We have received several sign-ups from your connection. Please try again later.',
        'error'   => 'Sorry, we could not sign you up just now. Please try again later.',
    ];
}

function newsletter_rate_limited(string $ip): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM Subscribers WHERE ConsentIP = ? AND ConsentDate >= NOW() - INTERVAL 1 HOUR',
        [$ip]
    ) >= NEWSLETTER_MAX_PER_HOUR;
}

/**
 * Add or re-subscribe someone, and email them a confirmation link.
 * Returns 'confirm_sent', 'pending' (a link was sent minutes ago) or
 * 'subscribed' (already confirmed; nothing is sent). Visitors see the same
 * message for every result, so the form can't reveal who is subscribed.
 *
 * $source is a Subscribers.Source value.
 */
function newsletter_subscribe(string $email, string $name, string $source): string
{
    $email = strtolower(trim($email));
    $name  = mb_substr(trim($name), 0, 100);

    $existing = db_row(
        'SELECT SubscriberID, SubscriptionStatus, ConfirmedDate,
                UpdatedDate >= NOW() - INTERVAL ' . NEWSLETTER_RESEND_MINUTES . ' MINUTE AS Recent
         FROM Subscribers WHERE Email = ?',
        [$email]
    );

    if ($existing !== null && $existing['SubscriptionStatus'] === 'Subscribed') {
        if ($existing['ConfirmedDate'] !== null) {
            return 'subscribed';
        }
        if ($existing['Recent']) {
            return 'pending';
        }
    }

    // The rule documented in CMS_ARCHITECTURE.md: adds a new person, refreshes
    // an unconfirmed one, or re-subscribes someone who left (who must confirm
    // again). SubscriptionStatus must change after the two IF lines read it.
    db_query(
        "INSERT INTO Subscribers (Name, Email, Source, ConsentDate, ConsentIP, UnsubscribeToken)
         VALUES (?, ?, ?, NOW(), ?, ?)
         ON DUPLICATE KEY UPDATE
             SubscribedDate = IF(SubscriptionStatus = 'Unsubscribed', CURRENT_TIMESTAMP, SubscribedDate),
             ConfirmedDate  = IF(SubscriptionStatus = 'Unsubscribed', NULL, ConfirmedDate),
             ConsentDate    = VALUES(ConsentDate),
             ConsentIP      = VALUES(ConsentIP),
             SubscriptionStatus = 'Subscribed',
             UnsubscribedDate = NULL,
             Name = COALESCE(Name, VALUES(Name)),
             UpdatedDate = CURRENT_TIMESTAMP",
        [$name === '' ? null : $name, $email, $source, client_ip(), bin2hex(random_bytes(32))]
    );

    $subscriber = db_row('SELECT SubscriberID, Name, UnsubscribeToken FROM Subscribers WHERE Email = ?', [$email]);
    activity_log_visitor($existing === null ? 'newsletter_signup' : 'newsletter_resubscribe', 'Subscribers', (int) $subscriber['SubscriberID'], $source);

    $unsubscribeUrl = absolute_url('newsletter.php?unsubscribe=' . $subscriber['UnsubscribeToken']);
    mail_send_later($email, 'newsletter-confirm', [
        'name'           => (string) ($subscriber['Name'] ?? ''),
        'confirmUrl'     => absolute_url('newsletter.php?confirm=' . $subscriber['UnsubscribeToken']),
        'unsubscribeUrl' => $unsubscribeUrl,
    ], [
        'to_name' => (string) ($subscriber['Name'] ?? ''),
        'headers' => ['List-Unsubscribe' => '<' . $unsubscribeUrl . '>'],
    ]);

    return 'confirm_sent';
}

/** The subscriber for a link token, or null for a malformed or unknown token. */
function newsletter_find(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return db_row('SELECT SubscriberID, Email, SubscriptionStatus, ConfirmedDate FROM Subscribers WHERE UnsubscribeToken = ?', [$token]);
}

/** Confirm a sign-up. Returns 'confirmed', 'already', 'unsubscribed' or 'invalid'. */
function newsletter_confirm(string $token): string
{
    $subscriber = newsletter_find($token);
    if ($subscriber === null) {
        return 'invalid';
    }
    if ($subscriber['SubscriptionStatus'] !== 'Subscribed') {
        return 'unsubscribed';
    }
    if ($subscriber['ConfirmedDate'] !== null) {
        return 'already';
    }

    db_query('UPDATE Subscribers SET ConfirmedDate = NOW() WHERE SubscriberID = ? AND ConfirmedDate IS NULL', [$subscriber['SubscriberID']]);
    activity_log_visitor('newsletter_confirm', 'Subscribers', (int) $subscriber['SubscriberID']);
    return 'confirmed';
}

/** Opt out. Returns 'done', 'already' or 'invalid'. */
function newsletter_unsubscribe(string $token): string
{
    $subscriber = newsletter_find($token);
    if ($subscriber === null) {
        return 'invalid';
    }
    if ($subscriber['SubscriptionStatus'] === 'Unsubscribed') {
        return 'already';
    }

    db_query(
        "UPDATE Subscribers SET SubscriptionStatus = 'Unsubscribed', UnsubscribedDate = NOW() WHERE SubscriberID = ?",
        [$subscriber['SubscriberID']]
    );
    activity_log_visitor('newsletter_unsubscribe', 'Subscribers', (int) $subscriber['SubscriberID']);
    return 'done';
}
