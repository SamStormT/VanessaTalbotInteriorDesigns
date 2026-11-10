<?php
/**
 * To the business: a new contact form message.
 *
 * @var array  $message   The saved ContactMessages row
 * @var string $typeLabel Enquiry type as shown to visitors
 * @var string $adminUrl  The message in the admin area
 */

$name    = trim($message['Name'] . ' ' . ($message['Surname'] ?? ''));
$details = [
    'From'         => $name,
    'Email'        => $message['Email'],
    'Phone'        => $message['Phone'] ?? '',
    'Enquiry type' => $typeLabel,
    'Subject'      => $message['Subject'] ?? '',
    'Newsletter'   => $message['SubscribeToNewsletter'] ? 'Asked to subscribe (a confirmation email was sent)' : '',
    'Received'     => date('j F Y, H:i', strtotime($message['CreatedDate'])),
];

return [
    'subject' => 'New enquiry: ' . $typeLabel . ' from ' . $name,
    'heading' => 'New website enquiry',
    'html'    => mail_details($details)
               . '<div style="padding:16px 20px;background:#FBF3EC;border-left:3px solid #CDA02D;margin:0 0 20px">' . mail_paragraphs($message['Message']) . '</div>'
               . '<p style="margin:0 0 8px">Reply to this email to answer ' . e($message['Name']) . ' directly.</p>'
               . mail_button($adminUrl, 'Open in admin'),
    'text'    => "New website enquiry\n\n" . mail_details_text($details) . "\n\nMessage:\n" . $message['Message']
               . "\n\nReply to this email to answer " . $message['Name'] . " directly.\nOpen in admin: " . $adminUrl,
    'footer'  => 'Sent by the contact form on your website. The message is also saved under Contact Messages in the admin area.',
];
