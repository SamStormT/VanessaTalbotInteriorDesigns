<?php
/**
 * Email: every message the site sends goes through mail_send().
 *
 * Messages are built from templates in includes/mail-templates/. A template
 * receives its data as variables and returns the subject, a heading, the HTML
 * body and a plain-text body; mail_send() wraps the HTML in the branded
 * layout and sends both versions. A new kind of email is one new template.
 *
 * Sending never throws: a failure is logged and mail_send() returns false, so
 * a mail-server problem can't lose the record that triggered the email.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if (is_file(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

/** Why email can't be sent, or null when it can. */
function mail_problem(): ?string
{
    if (!class_exists(PHPMailer::class)) {
        return 'PHPMailer is not installed (run composer install, or upload the vendor folder)';
    }
    if (!filter_var(MAIL_FROM_ADDRESS, FILTER_VALIDATE_EMAIL)) {
        return 'MAIL_FROM_ADDRESS is missing or invalid in .env';
    }
    if (MAIL_MAILER === 'smtp' && MAIL_HOST === '') {
        return 'MAIL_HOST is missing in .env';
    }
    if (APP_URL === '' && APP_ENV === 'production') {
        return 'APP_URL is missing in .env, so email links cannot be built';
    }
    return null;
}

/**
 * Full address of a page on this site, for links in emails. Uses APP_URL;
 * only in development does it fall back to the address being browsed.
 */
function absolute_url(string $path): string
{
    return mail_origin() . url($path);
}

/** The site's origin (https://domain) for links in emails. See absolute_url(). */
function mail_origin(): string
{
    $origin = APP_URL;
    if ($origin === '' && APP_ENV === 'development') {
        $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $origin = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }
    return $origin;
}

// ---------------------------------------------------------------------------
// Template helpers (for use inside includes/mail-templates/*.php)
// ---------------------------------------------------------------------------

/** Plain text as HTML paragraphs. */
function mail_paragraphs(string $text): string
{
    $html = '';
    foreach (preg_split('/\R{2,}/', trim($text)) ?: [] as $paragraph) {
        $html .= '<p style="margin:0 0 16px">' . nl2br(e($paragraph)) . '</p>';
    }
    return $html;
}

/** A call-to-action button that still works in clients that strip styles. */
function mail_button(string $href, string $label): string
{
    return '<p style="margin:24px 0"><a href="' . e($href) . '" style="display:inline-block;padding:12px 24px;'
         . 'background:#243A39;color:#FFFFFF;text-decoration:none;font-weight:600;letter-spacing:0.04em">'
         . e($label) . '</a></p>';
}

/** A two-column table of labels and plain-text values; empty values are skipped. */
function mail_details(array $rows): string
{
    $html = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px;border-collapse:collapse">';
    foreach ($rows as $label => $value) {
        if ((string) $value === '') {
            continue;
        }
        $html .= '<tr><th scope="row" style="text-align:left;vertical-align:top;padding:6px 12px 6px 0;width:34%;color:#7A6230;font-weight:600">'
               . e((string) $label) . '</th><td style="padding:6px 0;vertical-align:top">' . nl2br(e((string) $value)) . '</td></tr>';
    }
    return $html . '</table>';
}

/** Plain-text version of mail_details(). */
function mail_details_text(array $rows): string
{
    $lines = [];
    foreach ($rows as $label => $value) {
        if ((string) $value !== '') {
            $lines[] = $label . ': ' . $value;
        }
    }
    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Rendering and sending
// ---------------------------------------------------------------------------

/**
 * Render a template. Returns subject, html (wrapped in the layout) and text.
 *
 * @return array{subject:string,html:string,text:string}
 */
function mail_render(string $template, array $data): array
{
    if (!preg_match('/^[a-z0-9-]+$/', $template) || !is_file($file = INCLUDES_PATH . '/mail-templates/' . $template . '.php')) {
        throw new InvalidArgumentException('Unknown email template: ' . $template);
    }

    $parts = (static function (string $__file, array $__data): array {
        extract($__data, EXTR_SKIP);
        return require $__file;
    })($file, $data);

    $footer     = (string) ($parts['footer'] ?? '');
    $footerText = (string) ($parts['footer_text'] ?? html_entity_decode(strip_tags($footer), ENT_QUOTES, 'UTF-8'));
    $html       = mail_layout((string) $parts['heading'], (string) $parts['html'], $footer);
    $text       = trim((string) $parts['text']) . "\n\n--\n" . SITE_NAME . "\n" . SITE_TAGLINE
                . (trim($footerText) !== '' ? "\n\n" . trim($footerText) : '') . "\n";

    return [
        'subject' => trim(str_replace(["\r", "\n"], ' ', (string) $parts['subject'])),
        'html'    => $html,
        'text'    => $text,
    ];
}

/** The branded wrapper shared by every email. Inline styles only, for email clients. */
function mail_layout(string $heading, string $body, string $footer): string
{
    return '<!DOCTYPE html><html lang="en-ZA"><head><meta charset="UTF-8">'
         . '<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($heading) . '</title></head>'
         . '<body style="margin:0;padding:0;background:#F6E2D4">'
         . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6E2D4"><tr><td align="center" style="padding:24px 12px">'
         . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border-top:4px solid #243A39">'
         . '<tr><td style="padding:28px 32px 8px;font-family:Georgia,\'Times New Roman\',serif;color:#243A39">'
         . '<div style="font-size:22px;line-height:1.2">' . e(SITE_NAME) . '</div>'
         . '<div style="font-size:13px;color:#7A6230;font-style:italic;margin-top:4px">' . e(SITE_TAGLINE) . '</div></td></tr>'
         . '<tr><td style="padding:16px 32px 8px;font-family:\'Open Sans\',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#2B2B2B">'
         . '<h1 style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:20px;color:#243A39">' . e($heading) . '</h1>'
         . $body . '</td></tr>'
         . '<tr><td style="padding:16px 32px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6B6B6B;border-top:1px solid #EFE4DA">'
         . ($footer !== '' ? $footer . '<br><br>' : '')
         . '&copy; ' . date('Y') . ' ' . e(SITE_NAME) . '</td></tr>'
         . '</table></td></tr></table></body></html>';
}

/**
 * Send a templated email. Returns true when it was handed to the mail server
 * (or written to the log in log mode).
 *
 * Options: to_name, reply_to, reply_name, headers (extra headers, name => value).
 */
function mail_send(string $to, string $template, array $data = [], array $options = []): bool
{
    try {
        $message = mail_render($template, $data);
    } catch (Throwable $error) {
        error_log('Email not sent (' . $template . '): ' . $error->getMessage());
        return false;
    }
    return mail_deliver($to, $template, $message, $options) === null;
}

/**
 * Send an already-built message: ['subject' => ..., 'html' => ..., 'text' => ...].
 * Used by mail_send() and by newsletter campaigns, which build their own
 * complete HTML. Returns null on success, or the reason it failed (also logged).
 */
function mail_deliver(string $to, string $label, array $message, array $options = []): ?string
{
    $problem = mail_problem();
    if ($problem === null && !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $problem = 'invalid recipient address';
    }
    if ($problem !== null) {
        error_log('Email not sent (' . $label . '): ' . $problem);
        return $problem;
    }

    $mail = null;
    try {
        $mail = mail_create();

        $mail->addAddress($to, (string) ($options['to_name'] ?? ''));
        if (!empty($options['reply_to']) && filter_var($options['reply_to'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($options['reply_to'], (string) ($options['reply_name'] ?? ''));
        }
        foreach ($options['headers'] ?? [] as $name => $value) {
            $mail->addCustomHeader((string) $name, str_replace(["\r", "\n"], '', (string) $value));
        }

        $mail->Subject = trim(str_replace(["\r", "\n"], ' ', (string) $message['subject']));
        $mail->isHTML(true);
        $mail->Body    = (string) $message['html'];
        $mail->AltBody = (string) $message['text'];

        if (MAIL_MAILER === 'log') {
            return mail_write_to_log($mail, $label) ? null : 'could not write the email to the log';
        }

        $mail->send();
        return null;
    } catch (MailException $error) {
        $reason = trim(($mail->ErrorInfo ?? '') . ' ' . $error->getMessage());
    } catch (Throwable $error) {
        $reason = $error->getMessage();
    }
    error_log('Email not sent (' . $label . ' to ' . mail_domain($to) . '): ' . $reason);
    return mb_substr($reason, 0, 480);
}

/**
 * Send after the visitor's page has been delivered, so they don't wait for
 * the mail server and response time can't reveal whether an email was sent.
 */
function mail_send_later(string $to, string $template, array $data = [], array $options = []): void
{
    static $registered = false;
    static $queue = [];

    $queue[] = [$to, $template, $data, $options];

    if (!$registered) {
        $registered = true;
        register_shutdown_function(static function () use (&$queue): void {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            ignore_user_abort(true);
            foreach ($queue as [$to, $template, $data, $options]) {
                mail_send($to, $template, $data, $options);
            }
        });
    }
}

/** A configured PHPMailer instance. */
function mail_create(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->CharSet  = PHPMailer::CHARSET_UTF8;
    $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
    $mail->XMailer  = ' ';
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

    if (MAIL_MAILER === 'smtp') {
        $mail->isSMTP();
        $mail->Host    = MAIL_HOST;
        $mail->Port    = MAIL_PORT;
        $mail->Timeout = 15;

        // Development only: write the SMTP conversation to the error log.
        if (APP_ENV === 'development') {
            $mail->SMTPDebug   = SMTP::DEBUG_CONNECTION;
            $mail->Debugoutput = mail_debug_writer();
        }

        if (MAIL_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_ENCRYPTION === 'none') {
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        if (MAIL_USERNAME !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
        }
    }

    return $mail;
}

/**
 * Development only: SMTP debug lines go to the error log, prefixed "SMTP:".
 * PHPMailer already hides the username and password at this level; this also
 * hides the message itself, which can hold a reset link or a visitor's details.
 */
function mail_debug_writer(): callable
{
    $inBody = false;

    return static function (string $line) use (&$inBody): void {
        $line = trim($line);
        if ($line === '') {
            return;
        }
        if ($inBody) {
            if ($line === 'CLIENT -> SERVER: .') {
                $inBody = false;
                error_log('SMTP: CLIENT -> SERVER: [message content hidden]');
                error_log('SMTP: ' . $line);
            }
            return;
        }
        if (str_starts_with($line, 'SERVER -> CLIENT: 354')) {
            $inBody = true;
        }
        error_log('SMTP: ' . $line);
    };
}

/** Development: write the complete message to logs/mail/ instead of sending it. */
function mail_write_to_log(PHPMailer $mail, string $template): bool
{
    $folder = LOG_PATH . '/mail';
    if (!is_dir($folder) && !@mkdir($folder, 0750, true)) {
        error_log('Email not written: cannot create ' . $folder);
        return false;
    }

    $mail->preSend();
    $file = $folder . '/' . date('Ymd-His') . '-' . $template . '-' . bin2hex(random_bytes(3)) . '.eml';
    if (file_put_contents($file, $mail->getSentMIMEMessage()) === false) {
        error_log('Email not written: cannot write ' . $file);
        return false;
    }

    if (APP_ENV === 'production') {
        error_log('MAIL_MAILER=log in production: "' . $template . '" was written to the log, not sent');
    }
    return true;
}

/** The domain of an address, for logs that shouldn't hold the full address. */
function mail_domain(string $address): string
{
    return substr((string) strrchr($address, '@'), 1) ?: 'unknown';
}
