<?php
/**
 * llms.txt: a plain summary of the business for AI assistants (ChatGPT,
 * Claude, Perplexity and others), following the llmstxt.org format. .htaccess
 * serves this file at /llms.txt. Everything comes from the CMS, so it stays
 * up to date: who, where, services, key pages, contact details, the FAQ and
 * recent articles. Only published content is included.
 *
 * Needs APP_URL (the live https address): the links must be full addresses.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/blog.php';
require_once INCLUDES_PATH . '/seo.php';

header('Content-Type: text/plain; charset=UTF-8');
if (mail_origin() === '') {
    http_response_code(503);
    echo "llms.txt needs APP_URL to be set.\n";
    exit;
}

/** One line of text: no line breaks or markdown that would break the list. */
$line = static fn(?string $text): string => trim((string) preg_replace('/\s+/u', ' ', str_replace(['[', ']'], ['(', ')'], (string) $text)));

$settings = site_settings();
$search   = site_search_settings();
$owner    = $line($search['OwnerName'] ?? '');
$title    = $line($search['OwnerTitle'] ?? '');
$place    = implode(', ', array_filter([$settings['City'] ?? '', $settings['Province'] ?? '', $settings['Country'] ?? '']));
$bio      = $line(seo_short_bio());

$out   = [];
$out[] = '# ' . SITE_NAME;
$out[] = '';
$out[] = '> ' . $line(site_tagline() ?: SITE_TAGLINE) . '.' . ($bio !== '' ? ' ' . $bio : '');
$out[] = '';
$facts = array_filter([
    $owner !== '' ? $owner . ($title !== '' ? ', ' . $title : '') . '.' : '',
    $place !== '' ? 'Based in ' . $place . '.' : '',
]);
if ($facts) {
    $out[] = implode(' ', $facts);
    $out[] = '';
}

if ($areas = site_service_areas()) {
    $out[] = '## Where we work';
    $out[] = '';
    foreach ($areas as $area) {
        $out[] = '- ' . $line($area);
    }
    $out[] = '';
}

if ($services = site_services()) {
    $out[] = '## Services';
    $out[] = '';
    foreach ($services as $service) {
        if (!is_file(__DIR__ . '/services/' . $service['Slug'] . '.php')) {
            continue;
        }
        $about = $line(($service['ShortDescription'] ?? '') ?: ($service['Lead'] ?? ''));
        $out[] = '- [' . $line($service['ServiceName']) . '](' . absolute_url('services/' . $service['Slug'] . '.php') . ')' . ($about !== '' ? ': ' . $about : '');
    }
    $out[] = '';
}

$out[] = '## Key pages';
$out[] = '';
foreach ([
    ['About', 'about.php', 'who ' . ($owner ?: 'we are') . ($owner ? ' is' : '') . ', her background and approach'],
    ['Services', 'services.php', 'everything offered, and how it works'],
    ['Portfolio', 'portfolio.php', 'projects and photography'],
    ['Blog', 'blog.php', 'articles on biophilic design, colour and wellbeing'],
    ['Book a consultation', 'book-consultation.php', 'request a consultation with preferred dates'],
    ['Contact', 'contact.php', 'phone, email and frequently asked questions'],
] as [$label, $path, $note]) {
    $out[] = '- [' . $label . '](' . absolute_url($path) . '): ' . $note;
}
$out[] = '';

$contact = array_filter([
    trim((string) ($settings['Email'] ?? '')) !== '' ? '- Email: ' . trim((string) $settings['Email']) : '',
    trim((string) ($settings['Phone'] ?? '')) !== '' ? '- Phone: ' . trim((string) $settings['Phone']) : '',
    '- Book a consultation: ' . absolute_url('book-consultation.php'),
]);
$out[] = '## Contact';
$out[] = '';
array_push($out, ...$contact);
$out[] = '';

$faq = site_sections('contact')['faq']['items'] ?? [];
if ($faq) {
    $out[] = '## Frequently asked questions';
    $out[] = '';
    foreach ($faq as $item) {
        if (trim((string) $item['Title']) !== '' && trim((string) $item['Text']) !== '') {
            $out[] = '- **' . $line($item['Title']) . '** ' . $line($item['Text']);
        }
    }
    $out[] = '';
}

if ($posts = blog_latest(15)) {
    $out[] = '## Recent articles';
    $out[] = '';
    foreach ($posts as $post) {
        $summary = $line($post['Excerpt'] ?? '');
        $out[] = '- [' . $line($post['Title']) . '](' . absolute_url('blog/blog-post.php?slug=' . $post['Slug']) . ')' . ($summary !== '' ? ': ' . $summary : '');
    }
    $out[] = '';
}

echo implode("\n", $out);
