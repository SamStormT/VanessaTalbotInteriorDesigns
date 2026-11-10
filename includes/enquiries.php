<?php
/**
 * Contact form: validation, saving to ContactMessages, the newsletter
 * tick-box, and the notification email to the business.
 *
 * The message is saved before any email is attempted, so a mail-server
 * problem never loses an enquiry: it is always in Contact Messages.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mail.php';
require_once INCLUDES_PATH . '/newsletter.php';

const CONTACT_MAX_PER_HOUR = 5;    // messages from one IP address
const CONTACT_MESSAGE_MAX  = 5000; // characters

/** ContactMessages.EnquiryType values and the labels visitors see. */
function enquiry_types(): array
{
    return [
        'General'             => 'General enquiry',
        'Biophilic Design'    => 'Biophilic Design',
        'Colour Psychology'   => 'Colour & Design Psychology',
        'Photography'         => 'Photography',
        'Workshop'            => 'Workshop',
        'Speaking Engagement' => 'Speaking Engagement',
        'Consultation'        => 'Consultation',
        'Collaboration'       => 'Collaboration',
    ];
}

/** Enquire buttons link to contact.php?service=slug; this picks the matching type. */
function enquiry_type_for_service(string $slug): string
{
    return [
        'biophilic-design'     => 'Biophilic Design',
        'colour-psychology'    => 'Colour Psychology',
        'photography-services' => 'Photography',
        'workshops'            => 'Workshop',
        'speaking-engagements' => 'Speaking Engagement',
        'consultations'        => 'Consultation',
    ][$slug] ?? 'General';
}

/** ContactMessages.Status values and their admin labels. */
function enquiry_statuses(): array
{
    return ['New' => 'New', 'InProgress' => 'In progress', 'Responded' => 'Responded', 'Closed' => 'Closed'];
}

/** Single-line text from a form: trimmed, with control characters removed. */
function form_text(array $input, string $key): string
{
    $value = $input[$key] ?? '';
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
        return '';
    }
    return trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $value));
}

/** Multi-line text from a form: keeps line breaks, removes other control characters. */
function form_multiline(array $input, string $key): string
{
    $value = $input[$key] ?? '';
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
        return '';
    }
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return trim((string) preg_replace('/[^\P{Cc}\n\t]+/u', '', $value));
}

/**
 * Check a contact form submission.
 * Returns [data, errors]; errors are keyed by form field name.
 *
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function contact_validate(array $input): array
{
    $data = [
        'Name'                  => form_text($input, 'name'),
        'Surname'               => form_text($input, 'surname'),
        'Email'                 => form_text($input, 'email'),
        'Phone'                 => form_text($input, 'phone'),
        'Subject'               => form_text($input, 'subject'),
        'Message'               => form_multiline($input, 'message'),
        'EnquiryType'           => form_text($input, 'enquiry_type'),
        'SubscribeToNewsletter' => !empty($input['newsletter']) ? 1 : 0,
    ];
    $errors = [];

    if ($data['Name'] === '') {
        $errors['name'] = 'Please enter your first name.';
    } elseif (mb_strlen($data['Name']) > 100) {
        $errors['name'] = 'Please use 100 characters or fewer.';
    }
    if (mb_strlen($data['Surname']) > 100) {
        $errors['surname'] = 'Please use 100 characters or fewer.';
    }
    if ($data['Email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (mb_strlen($data['Email']) > 254 || !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address, like name@example.com.';
    }
    if ($data['Phone'] !== '' && !preg_match('/^[0-9+()\-.\s]{6,30}$/', $data['Phone'])) {
        $errors['phone'] = 'Please enter a phone number using digits, spaces and + only.';
    }
    if (mb_strlen($data['Subject']) > 255) {
        $errors['subject'] = 'Please use 255 characters or fewer.';
    }
    if ($data['Message'] === '') {
        $errors['message'] = 'Please enter your message.';
    } elseif (mb_strlen($data['Message']) > CONTACT_MESSAGE_MAX) {
        $errors['message'] = 'Please keep your message under ' . number_format(CONTACT_MESSAGE_MAX) . ' characters.';
    }
    if (!array_key_exists($data['EnquiryType'], enquiry_types())) {
        $data['EnquiryType'] = 'General';
    }

    foreach (['Surname', 'Phone', 'Subject'] as $optional) {
        $data[$optional] = $data[$optional] === '' ? null : $data[$optional];
    }

    return [$data, $errors];
}

function contact_rate_limited(string $ip): bool
{
    return (int) db_value(
        'SELECT COUNT(*) FROM ContactMessages WHERE IPAddress = ? AND CreatedDate >= NOW() - INTERVAL 1 HOUR',
        [$ip]
    ) >= CONTACT_MAX_PER_HOUR;
}

/** Where notifications go: Website Settings "Enquiry email", then the public email, then the sending address. */
function contact_recipient(): ?string
{
    $settings = db_row('SELECT EnquiryEmail, Email FROM WebsiteSettings WHERE SettingID = 1') ?? [];
    foreach ([$settings['EnquiryEmail'] ?? '', $settings['Email'] ?? '', MAIL_FROM_ADDRESS] as $address) {
        if (filter_var((string) $address, FILTER_VALIDATE_EMAIL)) {
            return (string) $address;
        }
    }
    return null;
}

/**
 * Save a validated message, handle the newsletter tick-box and queue the
 * notification email. Returns the new MessageID.
 */
function contact_submit(array $data): int
{
    db_query(
        'INSERT INTO ContactMessages (Name, Surname, Email, Phone, Subject, Message, EnquiryType, SubscribeToNewsletter, IPAddress)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$data['Name'], $data['Surname'], $data['Email'], $data['Phone'], $data['Subject'], $data['Message'],
         $data['EnquiryType'], $data['SubscribeToNewsletter'], client_ip()]
    );
    $id = (int) db()->lastInsertId();
    activity_log_visitor('contact_message', 'ContactMessages', $id, $data['EnquiryType']);

    // The message is safe; a newsletter problem must not turn it into an error.
    if ($data['SubscribeToNewsletter']) {
        try {
            newsletter_subscribe($data['Email'], $data['Name'], 'Contact Form');
        } catch (Throwable $error) {
            error_log('Newsletter sign-up from contact message ' . $id . ' failed: ' . $error->getMessage());
        }
    }

    $recipient = contact_recipient();
    if ($recipient === null) {
        error_log('Contact message ' . $id . ' saved, but no notification address is set in Website Settings');
        return $id;
    }

    $message = db_row('SELECT * FROM ContactMessages WHERE MessageID = ?', [$id]);
    mail_send_later($recipient, 'contact-notification', [
        'message'   => $message,
        'typeLabel' => enquiry_types()[$message['EnquiryType']] ?? $message['EnquiryType'],
        'adminUrl'  => absolute_url('admin/enquiry.php?id=' . $id),
    ], [
        'reply_to'   => $message['Email'],
        'reply_name' => trim($message['Name'] . ' ' . ($message['Surname'] ?? '')),
    ]);

    return $id;
}
