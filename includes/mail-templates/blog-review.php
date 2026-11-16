<?php
/**
 * To Admins: an Editor has asked for a blog post to be published.
 *
 * @var string $title     The post's title
 * @var string $editor    Who asked
 * @var string $adminUrl  The post in the admin area
 * @var string $previewUrl The post on the website (staff preview)
 */

return [
    'subject' => 'Ready to publish: ' . $title,
    'heading' => 'A blog post is ready to publish',
    'html'    => '<p style="margin:0 0 16px">' . e($editor) . ' has finished <strong>' . e($title) . '</strong> and asked for it to be published.</p>'
               . '<p style="margin:0 0 16px">Read it through, then choose Publish now or Schedule.</p>'
               . mail_button($adminUrl, 'Open the post')
               . '<p style="margin:16px 0 0"><a href="' . e($previewUrl) . '" style="color:#7A6230">Preview it on the website</a> (you need to be signed in).</p>',
    'text'    => $editor . ' has finished "' . $title . "\" and asked for it to be published.\n\n"
               . "Read it through, then choose Publish now or Schedule.\n\nOpen the post: " . $adminUrl . "\nPreview on the website: " . $previewUrl,
    'footer'  => 'Sent because you can publish blog posts on the website.',
];
