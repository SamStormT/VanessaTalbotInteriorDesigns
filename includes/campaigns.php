<?php
/**
 * Newsletter campaigns: content, the branded email, test sends and sending.
 *
 * A campaign is written once (usually from a blog post), previewed, tested,
 * marked Ready, then sent. Sending happens in small batches so the mail
 * server's limits are respected and an interrupted send can carry on:
 *
 *   1. campaign_start() records every confirmed subscriber as a Pending
 *      recipient, at that moment.
 *   2. campaign_process() claims a few Pending rows at a time, checks each
 *      person is *still* subscribed and confirmed, and emails them.
 *   3. When nothing is left, the campaign becomes Sent.
 *
 * Nobody unconfirmed or unsubscribed is ever emailed, and nobody is emailed
 * twice for the same campaign (one row per person per campaign).
 *
 * A Ready campaign can instead be Scheduled for a date and time. campaign_run()
 * starts it when that time comes; it is called by a scheduled task (cron), by
 * the web address scheduled-tasks.php, or when someone opens the campaign.
 *
 * Also here: the welcome email sent when someone confirms their subscription
 * (Marketing → Welcome Email), which uses the same branded design.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mail.php';
require_once INCLUDES_PATH . '/rich-text.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/blog.php';

const CAMPAIGN_BATCH_SIZE    = 10;  // emails per batch
const CAMPAIGN_CLAIM_MINUTES = 15;  // a claimed row older than this was interrupted

/** Most emails the site sends in any hour (campaigns and tests). Set NEWSLETTER_HOURLY_LIMIT in .env. */
function campaign_hourly_limit(): int
{
    return max(1, (int) env_value('NEWSLETTER_HOURLY_LIMIT', '200'));
}

function campaign_statuses(): array
{
    return [
        'Draft'    => 'Draft',
        'Ready'    => 'Ready to send',
        'Scheduled' => 'Scheduled',
        'Sending'  => 'Sending',
        'Sent'     => 'Sent',
        'Archived' => 'Archived',
    ];
}

/** Everyone who will receive a campaign sent now. */
function campaign_audience_count(): int
{
    return (int) db_value("SELECT COUNT(*) FROM Subscribers WHERE SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NOT NULL");
}

/**
 * The campaign form. Field types are those of includes/cms.php. Pass the
 * campaign being edited so articles it already uses stay choosable even if
 * they have since been unpublished.
 */
function campaign_module(array $campaign = []): array
{
    $choices = campaign_post_choices();
    foreach (['MorePost1ID', 'MorePost2ID', 'MorePost3ID'] as $field) {
        $id = (string) ($campaign[$field] ?? '');
        if ($id !== '' && !isset($choices[$id])) {
            $choices[$id] = 'An article that is no longer published';
        }
    }
    $more = static fn(string $label, string $help = ''): array => [
        'label' => $label, 'type' => 'select', 'options' => array_map('strval', array_keys($choices)), 'option_labels' => $choices,
        'group' => 'More articles (round-up)', 'help' => $help,
    ];

    return [
        'title'      => 'Newsletter Campaign',
        'table'      => 'NewsletterCampaigns',
        'key'        => 'CampaignID',
        'label'      => 'CampaignName',
        'permission' => 'subscribers',
        'fields'     => [
            'CampaignName'    => ['label' => 'Campaign name (only staff see this)', 'type' => 'text', 'required' => true, 'max' => 255, 'group' => 'Email'],
            'Subject'         => ['label' => 'Subject line', 'type' => 'text', 'required' => true, 'max' => 255, 'group' => 'Email', 'help' => 'You can use {first_name} for the subscriber\'s name.'],
            'PreviewText'     => ['label' => 'Inbox preview text', 'type' => 'text', 'max' => 255, 'group' => 'Email', 'help' => 'The short line many email apps show after the subject.'],
            'Greeting'        => ['label' => 'Greeting', 'type' => 'text', 'max' => 150, 'group' => 'Introduction', 'help' => '{first_name} becomes the subscriber\'s first name, or "there" if we don\'t know it: "Hi {first_name}," becomes "Hi Thandi," or "Hi there,".'],
            'IntroMessage'    => ['label' => 'Introduction message', 'type' => 'textarea', 'rich' => true, 'max' => 10000, 'group' => 'Introduction'],
            'FeatureTitle'    => ['label' => 'Article title', 'type' => 'text', 'max' => 255, 'group' => 'Featured article'],
            'FeatureSummary'  => ['label' => 'Article summary', 'type' => 'textarea', 'max' => 1000, 'group' => 'Featured article'],
            'FeatureImage'    => ['label' => 'Article image', 'type' => 'image', 'alt_field' => 'FeatureImageAlt', 'max' => 500, 'group' => 'Featured article'],
            'FeatureImageAlt' => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Featured article'],
            'FeatureBody'     => ['label' => 'Article preview', 'type' => 'textarea', 'rich' => true, 'max' => 50000, 'group' => 'Featured article', 'help' => 'The opening of the article. Readers continue on the website with the Read more button.'],
            'ReadMoreText'    => ['label' => 'Read more button', 'type' => 'text', 'max' => 100, 'group' => 'Featured article'],
            'ReadMoreLink'    => ['label' => 'Read more link', 'type' => 'link', 'max' => 500, 'group' => 'Featured article'],
            'QuoteText'       => ['label' => 'Quote', 'type' => 'textarea', 'max' => 1000, 'group' => 'Quote', 'help' => 'Leave blank to leave the quote out.'],
            'QuoteAuthor'     => ['label' => 'Who said it', 'type' => 'text', 'max' => 150, 'group' => 'Quote'],
            'MorePost1ID'     => $more('Second article', 'Optional. Up to three more blog articles, each shown as a short card with its picture, summary and a link. Use them for a monthly round-up.'),
            'MorePost2ID'     => $more('Third article'),
            'MorePost3ID'     => $more('Fourth article'),
            'Content'         => ['label' => 'More in this issue', 'type' => 'textarea', 'rich' => true, 'max' => 50000, 'group' => 'More content', 'help' => 'Optional extra blocks, such as news, an event or your services. Use Section for coloured blocks like the newsletter design.'],
        ],
    ];
}

/** Published blog articles (newest first), as choices for the round-up fields. Scheduled ones say when they appear. */
function campaign_post_choices(): array
{
    $choices = [];
    try {
        foreach (db_all("SELECT BlogID, Title, PublishedDate FROM BlogPosts WHERE Status = 'Published' ORDER BY PublishedDate DESC, BlogID DESC LIMIT 200") as $post) {
            $scheduled = blog_is_scheduled($post + ['Status' => 'Published']);
            $choices[(string) $post['BlogID']] = $post['Title'] . ($scheduled ? ' (appears ' . date('j M Y', strtotime((string) $post['PublishedDate'])) . ')' : '');
        }
    } catch (Throwable $error) {
        // Before the blog tables exist there is nothing to choose.
    }
    return $choices;
}

/** The round-up articles chosen for a campaign, in order, without repeats or the featured article. */
function campaign_more_posts(array $c): array
{
    $ids = [];
    foreach (['MorePost1ID', 'MorePost2ID', 'MorePost3ID'] as $field) {
        $id = (int) ($c[$field] ?? 0);
        if ($id > 0 && $id !== (int) ($c['BlogID'] ?? 0) && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }
    if ($ids === []) {
        return [];
    }
    $rows = db_all(
        "SELECT BlogID, Title, Slug, Excerpt, FeaturedImage, FeaturedImageAlt, PublishedDate FROM BlogPosts
         WHERE Status = 'Published' AND BlogID IN (" . implode(',', array_fill(0, count($ids), '?')) . ')',
        $ids
    );
    $byId = array_column($rows, null, 'BlogID');
    return array_values(array_filter(array_map(static fn(int $id) => $byId[$id] ?? null, $ids)));
}

/**
 * Why the campaign's articles can't go out at $when (a timestamp; now by
 * default), or null. A link to an article that is unpublished, or not on
 * the website yet, would lead subscribers to a missing page.
 */
function campaign_article_problem(array $c, ?int $when = null): ?string
{
    $when ??= time();
    $ids = array_values(array_filter(array_map('intval', [$c['BlogID'] ?? 0, $c['MorePost1ID'] ?? 0, $c['MorePost2ID'] ?? 0, $c['MorePost3ID'] ?? 0])));
    if ($ids === []) {
        return null;
    }
    foreach (db_all('SELECT Title, Status, PublishedDate FROM BlogPosts WHERE BlogID IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids) as $post) {
        if ($post['Status'] !== 'Published') {
            return 'The article "' . $post['Title'] . '" is not published, so its link would not work. Publish it, or take it out of the campaign.';
        }
        if (!empty($post['PublishedDate']) && strtotime((string) $post['PublishedDate']) > $when) {
            return 'The article "' . $post['Title'] . '" only appears on the website on ' . date('j M Y \a\t H:i', strtotime((string) $post['PublishedDate'])) . '. Send the newsletter after that.';
        }
    }
    return null;
}

/** Starting content for a new campaign. */
function campaign_defaults(): array
{
    $home = site_home();
    return [
        'Greeting'     => 'Hi {first_name},',
        'ReadMoreText' => 'Read the full article',
        'QuoteText'    => $home['FeaturedQuote'] ?? '',
        'QuoteAuthor'  => $home['FeaturedQuoteAuthor'] ?? '',
        'Content'      => campaign_services_block(),
    ];
}

/** The "how I can help" block from the published services, in the design's green. */
function campaign_services_block(): string
{
    $services = site_services();
    if ($services === []) {
        return '';
    }
    $lines = ['[[section green]]', '## How I can help', ''];
    foreach ($services as $service) {
        $lines[] = '- **' . $service['ServiceName'] . '**' . (trim((string) $service['ShortDescription']) !== '' ? ': ' . trim((string) $service['ShortDescription']) : '');
    }
    $lines[] = '';
    $lines[] = '[[button Explore my services | services.php]]';
    $lines[] = '[[/section]]';
    return implode("\n", $lines);
}

/** A new campaign's content, filled in from a blog post. */
function campaign_from_post(array $post): array
{
    $quote = rich_first_quote($post['Content'] ?? '');
    return array_merge(campaign_defaults(), [
        'BlogID'          => (int) $post['BlogID'],
        'CampaignName'    => $post['Title'],
        'Subject'         => $post['Title'],
        'PreviewText'     => mb_substr(trim((string) ($post['Excerpt'] ?? '')), 0, 255),
        'FeatureTitle'    => $post['Title'],
        'FeatureSummary'  => $post['Excerpt'] ?? '',
        'FeatureImage'    => $post['FeaturedImage'] ?? '',
        'FeatureImageAlt' => $post['FeaturedImageAlt'] ?? '',
        'FeatureBody'     => rich_preview($post['Content'] ?? ''),
        'ReadMoreLink'    => 'blog/blog-post.php?slug=' . $post['Slug'],
    ], $quote ? ['QuoteText' => $quote[0], 'QuoteAuthor' => $quote[1]] : []);
}

/**
 * A new round-up: the newest article as the featured one, and the next three
 * as the other articles. Null when nothing is published yet.
 */
function campaign_roundup(): ?array
{
    $posts = blog_find(null, '', 4, 0)[0];
    if ($posts === []) {
        return null;
    }
    $first = db_row('SELECT * FROM BlogPosts WHERE BlogID = ?', [(int) $posts[0]['BlogID']]);
    if ($first === null) {
        return null;
    }
    $row = campaign_from_post($first);
    $row['CampaignName'] = 'Round-up, ' . date('F Y');
    foreach (array_slice($posts, 1, 3) as $index => $post) {
        $row['MorePost' . ($index + 1) . 'ID'] = (string) $post['BlogID'];
    }
    return $row;
}

/** What still needs filling in before a campaign can be marked Ready. */
function campaign_problems(array $c): array
{
    $problems = [];
    if (trim((string) ($c['Subject'] ?? '')) === '') {
        $problems[] = 'Add a subject line.';
    }
    if (trim((string) ($c['IntroMessage'] ?? '')) === '') {
        $problems[] = 'Write an introduction message.';
    }
    if (trim((string) ($c['FeatureTitle'] ?? '')) === '' && trim((string) ($c['Content'] ?? '')) === '') {
        $problems[] = 'Add a featured article or some content.';
    }
    if (trim((string) ($c['FeatureTitle'] ?? '')) !== '' && trim((string) ($c['ReadMoreLink'] ?? '')) === '') {
        $problems[] = 'Add the Read more link for the featured article.';
    }
    return $problems;
}

// ---------------------------------------------------------------------------
// The email
// ---------------------------------------------------------------------------

/**
 * Build the email for one subscriber (or a test). Returns subject, html,
 * text and the unsubscribe address.
 *
 * @param array|null $subscriber  ['Name' => ..., 'UnsubscribeToken' => ...], or null for a test
 */
function campaign_render(array $c, ?array $subscriber, string $testName = ''): array
{
    $settings = site_settings();
    $name     = trim((string) ($subscriber['Name'] ?? $testName));
    $first    = $name !== '' ? preg_split('/\s+/', $name)[0] : '';
    $personal = static fn(string $text): string => str_replace('{first_name}', $first !== '' ? $first : 'there', $text);

    $unsubscribe = $subscriber !== null && !empty($subscriber['UnsubscribeToken'])
        ? absolute_url('newsletter.php?unsubscribe=' . $subscriber['UnsubscribeToken'])
        : absolute_url('newsletter.php');

    $subject = $personal(trim((string) $c['Subject']));
    $greet   = $personal(trim((string) ($c['Greeting'] ?? '')));
    $intro   = $personal((string) ($c['IntroMessage'] ?? ''));

    $c['MorePosts'] ??= campaign_more_posts($c);

    $html = campaign_email_html($c, $settings, $subject, $greet, $intro, $unsubscribe, $subscriber === null);
    $text = campaign_email_text($c, $settings, $greet, $intro, $unsubscribe);

    return ['subject' => $subject, 'html' => $html, 'text' => $text, 'unsubscribe' => $unsubscribe];
}

/** Brand fonts and colours used in the email (email apps need inline styles). */
function campaign_style(string $part): string
{
    return [
        'serif'  => "font-family:Georgia,'Times New Roman',serif",
        'sans'   => "font-family:'Open Sans',Arial,Helvetica,sans-serif",
        'script' => "font-family:'Allison','Brush Script MT','Segoe Script',cursive",
        'label'  => 'font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:0.28em;text-transform:uppercase',
    ][$part];
}

function campaign_email_html(array $c, array $settings, string $subject, string $greeting, string $intro, string $unsubscribe, bool $isTest): string
{
    $origin   = mail_origin();
    $site     = absolute_url('');  // the home page, shown as the plain domain
    $serif    = campaign_style('serif');
    $sans     = campaign_style('sans');
    $script   = campaign_style('script');
    $label    = campaign_style('label');
    $tagline  = trim((string) ($settings['Tagline'] ?? '')) ?: SITE_TAGLINE;
    $row      = static fn(string $inner, string $style = ''): string => '<tr><td class="px" style="' . $style . '">' . $inner . '</td></tr>';

    // 1. Logo: the official file when supplied (assets/images/logo-email.png), otherwise the lettering.
    $logoFile = ROOT_PATH . '/assets/images/logo-email.png';
    $logo = is_file($logoFile)
        ? '<a href="' . e($site) . '"><img src="' . e($origin . asset('images/logo-email.png')) . '" alt="' . e(SITE_NAME) . '" width="200" style="display:block;margin:0 auto;width:200px;max-width:70%;height:auto;border:0"></a>'
        : '<a href="' . e($site) . '" style="text-decoration:none;color:#243A39"><span style="' . $serif . ';font-size:30px;line-height:1.05;color:#243A39;display:block">Vanessa<br>Talbot</span>'
          . '<span style="' . $label . ';font-size:10px;color:#9D7F3C;display:block;margin-top:6px">Interior Designs</span></a>';

    $html = '<tr><td align="center" style="padding:34px 24px 26px">' . $logo . '</td></tr>';

    // 2. Tagline on peach, in the script typeface.
    $html .= '<tr><td align="center" class="px" style="background:#F6E2D4;padding:34px 40px">'
           . '<p style="margin:0;' . $script . ';font-size:34px;line-height:1.2;color:#243A39">' . e($tagline) . '</p></td></tr>';

    // 3. Greeting and introduction, in the gold panel ("Hi there…").
    if ($greeting !== '' || trim($intro) !== '') {
        $html .= $row(
            ($greeting !== '' ? '<p style="margin:0 0 14px;' . $script . ';font-size:40px;line-height:1.1;color:#FFFFFF">' . e($greeting) . '</p>' : '')
            . rich_render($intro, 'email', 'gold'),
            'background:#9D7F3C;padding:34px 40px 22px'
        );
    }

    // 4. Featured article.
    if (trim((string) ($c['FeatureTitle'] ?? '')) !== '') {
        $image = trim((string) ($c['FeatureImage'] ?? ''));
        $more  = trim((string) ($c['ReadMoreLink'] ?? '')) !== '' ? rich_href((string) $c['ReadMoreLink'], 'email') : null;
        $inner = '<p style="margin:0 0 14px;' . $label . ';color:#9D7F3C">From the blog</p>'
               . ($image !== '' && link_is_safe($image)
                    ? '<a href="' . e($more ?? $site) . '"><img src="' . e($origin . image_url($image, 'web')) . '" alt="' . e($c['FeatureImageAlt'] ?? '') . '" width="520" style="display:block;width:100%;max-width:520px;height:auto;border:0;margin:0 0 20px"></a>'
                    : '')
               . '<h1 style="margin:0 0 12px;' . $serif . ';font-size:26px;line-height:1.25;font-weight:normal;color:#243A39">' . e($c['FeatureTitle']) . '</h1>'
               . (trim((string) ($c['FeatureSummary'] ?? '')) !== '' ? '<p style="margin:0 0 16px;' . $sans . ';font-size:16px;line-height:1.6;color:#7A6230">' . e($c['FeatureSummary']) . '</p>' : '')
               . rich_render((string) ($c['FeatureBody'] ?? ''), 'email')
               . ($more !== null ? rich_button(trim((string) ($c['ReadMoreText'] ?? '')) ?: 'Read more', (string) $c['ReadMoreLink'], 'email', '') : '');
        $html .= $row($inner, 'padding:38px 40px 16px');
    }

    // 4b. More articles (a round-up, or the newest articles in the welcome email).
    if (!empty($c['MorePosts'])) {
        $cards = '<p style="margin:0 0 18px;' . $label . ';color:#9D7F3C">' . e($c['MoreHeading'] ?? 'More from the blog') . '</p>';
        foreach ($c['MorePosts'] as $post) {
            $link  = absolute_url('blog/blog-post.php?slug=' . $post['Slug']);
            $image = trim((string) ($post['FeaturedImage'] ?? ''));
            $cards .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse"><tr>'
                    . ($image !== '' && link_is_safe($image)
                        ? '<td class="stack" width="150" valign="top" style="width:150px;padding:0 18px 0 0"><a href="' . e($link) . '"><img src="' . e($origin . image_url($image, 'thumb')) . '" alt="' . e($post['FeaturedImageAlt'] ?? '') . '" width="150" style="display:block;width:150px;max-width:100%;height:auto;border:0"></a></td>'
                        : '')
                    . '<td class="stack" valign="top"><h2 style="margin:0 0 6px;' . $serif . ';font-size:20px;line-height:1.3;font-weight:normal"><a href="' . e($link) . '" style="color:#243A39;text-decoration:none">' . e($post['Title']) . '</a></h2>'
                    . (trim((string) ($post['Excerpt'] ?? '')) !== '' ? '<p style="margin:0 0 8px;' . $sans . ';font-size:15px;line-height:1.6;color:#4F5E5D">' . e(trim((string) $post['Excerpt'])) . '</p>' : '')
                    . '<p style="margin:0;' . $sans . ';font-size:14px"><a href="' . e($link) . '" style="color:#7A6230;font-weight:600">Read the article</a></p>'
                    . '</td></tr></table>';
        }
        $html .= $row($cards, 'padding:' . (trim((string) ($c['FeatureTitle'] ?? '')) !== '' ? '10px' : '38px') . ' 40px 12px');
    }

    // 5. Quote on peach.
    if (trim((string) ($c['QuoteText'] ?? '')) !== '') {
        $html .= '<tr><td align="center" class="px" style="background:#F6E2D4;padding:36px 48px">'
               . '<p style="margin:0 0 14px;' . $serif . ';font-size:19px;line-height:1.55;font-style:italic;color:#243A39">&ldquo;' . e(trim((string) $c['QuoteText'])) . '&rdquo;</p>'
               . (trim((string) ($c['QuoteAuthor'] ?? '')) !== '' ? '<p style="margin:0;' . $script . ';font-size:30px;line-height:1.1;color:#9D7F3C">' . e(trim((string) $c['QuoteAuthor'])) . '</p>' : '')
               . '</td></tr>';
    }

    // 6. More content (supporting blocks).
    if (trim((string) ($c['Content'] ?? '')) !== '') {
        $html .= $row(rich_render((string) $c['Content'], 'email'), 'padding:34px 40px 14px');
    }

    // 7–8. Contact details and social links, from Website Settings.
    $phone   = trim((string) ($settings['Phone'] ?? ''));
    $email   = trim((string) ($settings['Email'] ?? ''));
    $contact = [];
    if ($phone !== '') {
        $contact[] = '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $phone)) . '" style="color:#7A6230;text-decoration:none">' . e($phone) . '</a>';
    }
    if ($email !== '') {
        $contact[] = '<a href="mailto:' . e($email) . '" style="color:#7A6230;text-decoration:none">' . e($email) . '</a>';
    }
    $contact[] = '<a href="' . e($site) . '" style="color:#7A6230;text-decoration:none">' . e(preg_replace('#^https?://#', '', rtrim($site, '/'))) . '</a>';
    $social = [];
    foreach (['Instagram', 'Facebook', 'LinkedIn', 'Pinterest'] as $network) {
        $link = trim((string) ($settings[$network] ?? ''));
        if ($link !== '' && preg_match('#^https?://#i', $link)) {
            $social[] = '<a href="' . e($link) . '" style="color:#243A39;text-decoration:underline">' . $network . '</a>';
        }
    }
    $html .= '<tr><td align="center" class="px" style="background:#FBF6F2;padding:30px 40px">'
           . '<p style="margin:0 0 10px;' . $script . ';font-size:32px;line-height:1.1;color:#243A39">Feel free to contact me</p>'
           . '<p style="margin:0;' . $sans . ';font-size:14px;line-height:1.8;color:#7A6230">' . implode(' &nbsp;&middot;&nbsp; ', $contact) . '</p>'
           . ($social ? '<p style="margin:12px 0 0;' . $sans . ';font-size:13px;line-height:1.8;color:#243A39">' . implode(' &nbsp;&middot;&nbsp; ', $social) . '</p>' : '')
           . '</td></tr>';

    // 9. Why they're receiving it, and unsubscribe (POPIA).
    $place = trim(implode(', ', array_filter([$settings['City'] ?? '', $settings['Country'] ?? ''])));
    $html .= '<tr><td align="center" class="px" style="background:#243A39;padding:26px 40px">'
           . '<p style="margin:0 0 8px;' . $serif . ';font-size:16px;color:#FFFFFF">' . e(SITE_NAME) . '</p>'
           . '<p style="margin:0 0 10px;' . $sans . ';font-size:12px;line-height:1.6;color:#D9CFC6">You are receiving this because you subscribed to our newsletter and confirmed your email address.'
           . ($place !== '' ? '<br>' . e($place) : '') . '</p>'
           . '<p style="margin:0;' . $sans . ';font-size:12px;line-height:1.6"><a href="' . e($unsubscribe) . '" style="color:#F6E2D4;text-decoration:underline">Unsubscribe</a>'
           . ($isTest ? ' <span style="color:#D9CFC6">(test email: this link is not personalised)</span>' : '') . '</p>'
           . '</td></tr>';

    $preview = trim((string) ($c['PreviewText'] ?? ''));

    return '<!DOCTYPE html><html lang="en-ZA"><head><meta charset="UTF-8">'
         . '<meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="x-apple-disable-message-reformatting">'
         . '<title>' . e($subject) . '</title>'
         . '<link href="https://fonts.googleapis.com/css2?family=Allison&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">'
         . '<style>body{margin:0;padding:0}img{-ms-interpolation-mode:bicubic}a{color:#7A6230}'
         . '@media (max-width:620px){.wrap{width:100%!important}.px{padding-left:22px!important;padding-right:22px!important}'
         . '.stack{display:block!important;width:100%!important;padding:0 0 12px!important}}</style>'
         . '</head><body style="margin:0;padding:0;background:#F9F0EA">'
         . ($preview !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:#F9F0EA">' . e($preview) . str_repeat('&nbsp;&zwnj;', 40) . '</div>' : '')
         . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F9F0EA"><tr><td align="center" style="padding:24px 10px">'
         . '<table role="presentation" class="wrap" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#FFFFFF;border-collapse:collapse">'
         . $html
         . '</table></td></tr></table></body></html>';
}

function campaign_email_text(array $c, array $settings, string $greeting, string $intro, string $unsubscribe): string
{
    $parts = [SITE_NAME, (trim((string) ($settings['Tagline'] ?? '')) ?: SITE_TAGLINE), ''];
    if ($greeting !== '') {
        $parts[] = $greeting;
    }
    $parts[] = rich_plain($intro);
    if (trim((string) ($c['FeatureTitle'] ?? '')) !== '') {
        array_push($parts, '', '--- FROM THE BLOG ---', trim((string) $c['FeatureTitle']), trim((string) ($c['FeatureSummary'] ?? '')), '', rich_plain($c['FeatureBody'] ?? ''));
        if (($link = rich_href((string) ($c['ReadMoreLink'] ?? ''), 'email')) !== null) {
            $parts[] = (trim((string) ($c['ReadMoreText'] ?? '')) ?: 'Read more') . ': ' . $link;
        }
    }
    if (!empty($c['MorePosts'])) {
        array_push($parts, '', '--- ' . strtoupper((string) ($c['MoreHeading'] ?? 'More from the blog')) . ' ---');
        foreach ($c['MorePosts'] as $post) {
            array_push($parts, '', trim((string) $post['Title']), trim((string) ($post['Excerpt'] ?? '')), absolute_url('blog/blog-post.php?slug=' . $post['Slug']));
        }
    }
    if (trim((string) ($c['QuoteText'] ?? '')) !== '') {
        array_push($parts, '', '"' . trim((string) $c['QuoteText']) . '"' . (trim((string) ($c['QuoteAuthor'] ?? '')) !== '' ? ' - ' . trim((string) $c['QuoteAuthor']) : ''));
    }
    if (trim((string) ($c['Content'] ?? '')) !== '') {
        array_push($parts, '', rich_plain($c['Content']));
    }
    array_push($parts, '', '--', 'Feel free to contact me: ' . implode(' | ', array_filter([$settings['Phone'] ?? '', $settings['Email'] ?? '', absolute_url('')])),
        '', 'You are receiving this because you subscribed to our newsletter and confirmed your email address.', 'Unsubscribe: ' . $unsubscribe);
    return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $parts))) . "\n";
}

// ---------------------------------------------------------------------------
// Test sends
// ---------------------------------------------------------------------------

/** Emails sent in the last hour (campaigns and tests), for the hourly limit. */
function campaign_sent_last_hour(): int
{
    return (int) db_value("SELECT COUNT(*) FROM CampaignRecipients WHERE Status = 'Sent' AND SentDate >= NOW() - INTERVAL 1 HOUR")
         + (int) db_value('SELECT COUNT(*) FROM CampaignTestSends WHERE Successful = 1 AND SentDate >= NOW() - INTERVAL 1 HOUR');
}

/** Send a test copy. Returns null on success, or the reason it failed. */
function campaign_send_test(array $c, string $to, array $actor): ?string
{
    if (campaign_sent_last_hour() >= campaign_hourly_limit()) {
        return 'The hourly sending limit has been reached. Please try again later.';
    }
    $message = campaign_render($c, null, (string) $actor['Name']);
    $message['subject'] = '[TEST] ' . $message['subject'];
    $error = mail_deliver($to, 'campaign-test', $message, ['to_name' => trim($actor['Name'] . ' ' . $actor['Surname'])]);

    db_query(
        'INSERT INTO CampaignTestSends (CampaignID, SentTo, SentBy, Successful, ErrorMessage) VALUES (?, ?, ?, ?, ?)',
        [(int) $c['CampaignID'], $to, (int) $actor['AdminUserID'], $error === null ? 1 : 0, $error]
    );
    activity_log('test_send', 'NewsletterCampaigns', (int) $c['CampaignID'], $error === null ? 'Test sent' : 'Test failed');
    return $error;
}

// ---------------------------------------------------------------------------
// Sending
// ---------------------------------------------------------------------------

/**
 * Start sending a Ready campaign: record everyone confirmed and subscribed
 * right now as a recipient. Returns null, or why it can't start.
 */
function campaign_start(int $id): ?string
{
    db()->beginTransaction();
    try {
        $c = db_row('SELECT *, ScheduledDate <= NOW() AS Due FROM NewsletterCampaigns WHERE CampaignID = ? FOR UPDATE', [$id]);
        if ($c === null || !($c['Status'] === 'Ready' || ($c['Status'] === 'Scheduled' && $c['Due']))) {
            db()->rollBack();
            return 'Only a campaign marked Ready can be sent.';
        }
        if (($problem = campaign_article_problem($c)) !== null) {
            db()->rollBack();
            return $problem;
        }
        db_query(
            "INSERT IGNORE INTO CampaignRecipients (CampaignID, SubscriberID)
             SELECT ?, SubscriberID FROM Subscribers
             WHERE SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NOT NULL",
            [$id]
        );
        $queued = (int) db_value('SELECT COUNT(*) FROM CampaignRecipients WHERE CampaignID = ?', [$id]);
        if ($queued === 0) {
            db()->rollBack();
            return 'There are no confirmed subscribers to send to yet.';
        }
        db_query("UPDATE NewsletterCampaigns SET Status = 'Sending', SendStartedDate = NOW(), RecipientCount = ? WHERE CampaignID = ?", [$queued, $id]);
        activity_log('send_start', 'NewsletterCampaigns', $id, $queued . ' recipients' . ($c['Status'] === 'Scheduled' ? ' (scheduled)' : ''));
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    return null;
}

/** Counts of recipients by result. */
function campaign_progress(int $id): array
{
    $counts = array_column(db_all('SELECT Status, COUNT(*) AS Total FROM CampaignRecipients WHERE CampaignID = ? GROUP BY Status', [$id]), 'Total', 'Status');
    $out = [];
    foreach (['Pending', 'Sending', 'Sent', 'Failed', 'Skipped'] as $status) {
        $out[strtolower($status)] = (int) ($counts[$status] ?? 0);
    }
    $out['total'] = array_sum($out);
    return $out;
}

/**
 * Send the next batch. Returns progress plus 'done' (true when finished),
 * 'paused' (true when the hourly limit was reached) and 'message'.
 */
function campaign_process(int $id, int $batch = CAMPAIGN_BATCH_SIZE): array
{
    $c = db_row('SELECT * FROM NewsletterCampaigns WHERE CampaignID = ?', [$id]);
    if ($c === null || $c['Status'] !== 'Sending') {
        return campaign_progress($id) + ['done' => $c !== null && $c['Status'] === 'Sent', 'paused' => false, 'message' => 'This campaign is not sending.'];
    }

    // A row claimed long ago was interrupted mid-send: it may or may not have
    // gone out, so it is marked Failed (never re-sent automatically).
    db_query(
        "UPDATE CampaignRecipients SET Status = 'Failed', ErrorMessage = 'Interrupted while sending; it may not have been delivered', ClaimToken = NULL
         WHERE CampaignID = ? AND Status = 'Sending' AND ClaimedDate < NOW() - INTERVAL " . CAMPAIGN_CLAIM_MINUTES . ' MINUTE',
        [$id]
    );

    $room = campaign_hourly_limit() - campaign_sent_last_hour();
    if ($room <= 0) {
        return campaign_progress($id) + ['done' => false, 'paused' => true, 'message' => 'The hourly sending limit has been reached. Sending continues when you press Resume after the hour.'];
    }

    // Claim a batch, so two open windows can never email the same person.
    $token = bin2hex(random_bytes(16));
    db_query(
        "UPDATE CampaignRecipients SET Status = 'Sending', ClaimToken = ?, ClaimedDate = NOW()
         WHERE CampaignID = ? AND Status = 'Pending' ORDER BY RecipientID LIMIT " . max(1, min($batch, $room)),
        [$token, $id]
    );
    $claimed = db_all(
        'SELECT r.RecipientID, s.SubscriberID, s.Name, s.Email, s.SubscriptionStatus, s.ConfirmedDate, s.UnsubscribeToken
         FROM CampaignRecipients r JOIN Subscribers s ON s.SubscriberID = r.SubscriberID
         WHERE r.ClaimToken = ?',
        [$token]
    );

    foreach ($claimed as $person) {
        // Checked again now: someone may have unsubscribed since sending began.
        if ($person['SubscriptionStatus'] !== 'Subscribed' || $person['ConfirmedDate'] === null) {
            db_query("UPDATE CampaignRecipients SET Status = 'Skipped', ErrorMessage = 'No longer subscribed', ClaimToken = NULL WHERE RecipientID = ?", [$person['RecipientID']]);
            continue;
        }
        $message = campaign_render($c, $person);
        $error   = mail_deliver($person['Email'], 'campaign', $message, [
            'to_name' => (string) ($person['Name'] ?? ''),
            'headers' => [
                'List-Unsubscribe'      => '<' . $message['unsubscribe'] . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        ]);
        db_query(
            'UPDATE CampaignRecipients SET Status = ?, SentDate = IF(? = 1, NOW(), NULL), ErrorMessage = ?, ClaimToken = NULL WHERE RecipientID = ?',
            [$error === null ? 'Sent' : 'Failed', $error === null ? 1 : 0, $error, $person['RecipientID']]
        );
    }

    $progress = campaign_progress($id);
    if ($progress['pending'] === 0 && $progress['sending'] === 0) {
        db_query("UPDATE NewsletterCampaigns SET Status = 'Sent', SentDate = NOW(), RecipientCount = ? WHERE CampaignID = ? AND Status = 'Sending'", [$progress['sent'], $id]);
        activity_log('send_complete', 'NewsletterCampaigns', $id, $progress['sent'] . ' sent, ' . $progress['failed'] . ' failed, ' . $progress['skipped'] . ' skipped');
        return $progress + ['done' => true, 'paused' => false, 'message' => 'Sent to ' . $progress['sent'] . ' subscriber' . ($progress['sent'] === 1 ? '' : 's') . '.'];
    }
    return $progress + ['done' => false, 'paused' => false, 'message' => 'Sending…'];
}

/** Try the failed recipients again (only those still subscribed will be emailed). */
function campaign_retry_failed(int $id): int
{
    $count = (int) db_value("SELECT COUNT(*) FROM CampaignRecipients WHERE CampaignID = ? AND Status = 'Failed'", [$id]);
    if ($count > 0) {
        db_query("UPDATE CampaignRecipients SET Status = 'Pending', ErrorMessage = NULL WHERE CampaignID = ? AND Status = 'Failed'", [$id]);
        db_query("UPDATE NewsletterCampaigns SET Status = 'Sending' WHERE CampaignID = ? AND Status = 'Sent'", [$id]);
        activity_log('send_retry', 'NewsletterCampaigns', $id, $count . ' failed recipients');
    }
    return $count;
}

// ---------------------------------------------------------------------------
// Scheduled sending
// ---------------------------------------------------------------------------

/** Schedule a Ready campaign for a date and time ($when as the browser sends it). Returns null, or why not. */
function campaign_schedule(int $id, string $when): ?string
{
    $at = cms_datetime($when);
    if ($at === null) {
        return 'Enter a valid date and time.';
    }
    if (strtotime($at) < time() + 60) {
        return 'Choose a time still to come. To send straight away, use Send campaign.';
    }
    if (strtotime($at) > strtotime('+1 year')) {
        return 'Choose a time within the next year.';
    }
    $c = db_row('SELECT * FROM NewsletterCampaigns WHERE CampaignID = ?', [$id]);
    if ($c === null || $c['Status'] !== 'Ready') {
        return 'Only a campaign marked Ready can be scheduled.';
    }
    if (($problem = campaign_article_problem($c, strtotime($at))) !== null) {
        return $problem;
    }
    db_query("UPDATE NewsletterCampaigns SET Status = 'Scheduled', ScheduledDate = ? WHERE CampaignID = ? AND Status = 'Ready'", [$at, $id]);
    activity_log('schedule', 'NewsletterCampaigns', $id, 'For ' . $at);
    return null;
}

/** Cancel a schedule: the campaign goes back to Ready. */
function campaign_unschedule(int $id): bool
{
    $done = db_query("UPDATE NewsletterCampaigns SET Status = 'Ready', ScheduledDate = NULL WHERE CampaignID = ? AND Status = 'Scheduled'", [$id])->rowCount() > 0;
    if ($done) {
        activity_log('unschedule', 'NewsletterCampaigns', $id);
    }
    return $done;
}

/** Scheduled campaigns whose time passed more than 20 minutes ago: the automatic sender isn't running. */
function campaign_overdue(): array
{
    try {
        return db_all("SELECT CampaignID, CampaignName, ScheduledDate FROM NewsletterCampaigns WHERE Status = 'Scheduled' AND ScheduledDate < NOW() - INTERVAL 20 MINUTE ORDER BY ScheduledDate");
    } catch (Throwable $error) {
        return [];
    }
}

/**
 * Start every scheduled campaign whose time has come, then send batches until
 * everything is sent, the hourly limit is reached, or $seconds have passed.
 * Safe to run at any time and from several places at once: nobody is ever
 * emailed twice. Returns a line for each thing that happened.
 */
function campaign_run(int $seconds): array
{
    $log = [];
    $end = time() + $seconds;

    foreach (db_all("SELECT CampaignID, CampaignName FROM NewsletterCampaigns WHERE Status = 'Scheduled' AND ScheduledDate <= NOW() ORDER BY ScheduledDate") as $c) {
        $problem = campaign_start((int) $c['CampaignID']);
        $log[] = $problem === null ? 'Started "' . $c['CampaignName'] . '" (scheduled)' : 'Could not start "' . $c['CampaignName'] . '": ' . $problem;
    }

    foreach (db_all("SELECT CampaignID, CampaignName FROM NewsletterCampaigns WHERE Status = 'Sending' ORDER BY SendStartedDate") as $c) {
        while (true) {
            $result = campaign_process((int) $c['CampaignID']);
            if ($result['paused']) {
                $log[] = '"' . $c['CampaignName'] . '": ' . $result['message'];
                return $log;
            }
            if ($result['done'] || $result['pending'] === 0) {
                break; // finished, or the last few are being sent elsewhere
            }
            if (time() >= $end) {
                $log[] = '"' . $c['CampaignName'] . '": ' . $result['sent'] . ' sent so far; the rest continue next time.';
                return $log;
            }
            sleep(1);
        }
        $log[] = sprintf('"%s": %d sent, %d failed, %d skipped, %d waiting', $c['CampaignName'], $result['sent'], $result['failed'], $result['skipped'], $result['pending'] + $result['sending']);
    }

    return $log === [] ? ['Nothing to send.'] : $log;
}

// ---------------------------------------------------------------------------
// Welcome email
// ---------------------------------------------------------------------------

/** The welcome email's settings, or null before the database is updated. */
function welcome_settings(): ?array
{
    try {
        return db_row('SELECT * FROM NewsletterSettings WHERE SettingID = 1');
    } catch (Throwable $error) {
        return null;
    }
}

/** Build the welcome email for one subscriber (or a test), in the newsletter design. */
function welcome_render(array $w, ?array $subscriber, string $testName = ''): array
{
    return campaign_render([
        'Subject'      => trim((string) $w['WelcomeSubject']) ?: 'Welcome',
        'Greeting'     => (string) ($w['WelcomeGreeting'] ?? ''),
        'IntroMessage' => (string) ($w['WelcomeMessage'] ?? ''),
        'MorePosts'    => (int) $w['WelcomeShowLatest'] === 1 ? blog_latest(3) : [],
        'MoreHeading'  => 'Start reading',
    ], $subscriber, $testName);
}

/**
 * Send the welcome email to someone who has just confirmed, after their page
 * has loaded. Does nothing when the welcome email is switched off.
 */
function welcome_send_later(int $subscriberId): void
{
    mail_later(static function () use ($subscriberId): void {
        $w = welcome_settings();
        if ($w === null || (int) $w['WelcomeEnabled'] !== 1) {
            return;
        }
        $person = db_row('SELECT Name, Email, UnsubscribeToken, SubscriptionStatus, ConfirmedDate FROM Subscribers WHERE SubscriberID = ?', [$subscriberId]);
        if ($person === null || $person['SubscriptionStatus'] !== 'Subscribed' || $person['ConfirmedDate'] === null) {
            return;
        }
        $message = welcome_render($w, $person);
        mail_deliver($person['Email'], 'newsletter-welcome', $message, [
            'to_name' => (string) ($person['Name'] ?? ''),
            'headers' => [
                'List-Unsubscribe'      => '<' . $message['unsubscribe'] . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        ]);
    });
}

/** Send a test copy of the welcome email. Returns null on success, or why it failed. */
function welcome_send_test(array $w, string $to, array $actor): ?string
{
    $message = welcome_render($w, null, (string) $actor['Name']);
    $message['subject'] = '[TEST] ' . $message['subject'];
    $error = mail_deliver($to, 'welcome-test', $message, ['to_name' => trim($actor['Name'] . ' ' . $actor['Surname'])]);
    activity_log('test_send', 'NewsletterSettings', 1, $error === null ? 'Welcome email test sent' : 'Welcome email test failed');
    return $error;
}
