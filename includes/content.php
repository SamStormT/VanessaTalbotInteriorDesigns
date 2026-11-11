<?php
/**
 * Public website content, read from the CMS.
 *
 * Only Published records are returned. If the database can't be reached,
 * pages fall back to the snapshot in content-fallback.php so the site keeps
 * working; the problem is written to the error log.
 *
 * All text is plain text and must be escaped with e() or the helpers below.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/page-map.php';

function content_fetch(string $key, callable $query): mixed
{
    static $databaseDown = false;
    static $fallback = null;

    if (!$databaseDown) {
        try {
            $result = $query();
            if ($result !== null) {
                return $result;
            }
        } catch (Throwable $error) {
            $databaseDown = true;
            error_log('Public content unavailable, using fallback: ' . $error->getMessage());
        }
    }

    $fallback ??= require INCLUDES_PATH . '/content-fallback.php';
    return $fallback[$key] ?? [];
}

function site_tagline(): string
{
    static $tagline = null;
    return $tagline ??= (string) (content_fetch('settings', static fn() => db_row('SELECT Tagline FROM WebsiteSettings WHERE SettingID = 1'))['Tagline'] ?? '');
}

function site_home(): array
{
    return content_fetch('home', static fn() => db_row('SELECT * FROM HomePageContent WHERE HomePageID = 1'));
}

function site_about(): array
{
    return content_fetch('about', static fn() => db_row('SELECT * FROM AboutContent WHERE AboutID = 1'));
}

function site_services(): array
{
    return content_fetch('services', static fn() => db_all("SELECT * FROM Services WHERE Status = 'Published' ORDER BY DisplayOrder, ServiceName"));
}

function site_values(): array
{
    return content_fetch('values', static fn() => db_all("SELECT * FROM AboutValues WHERE Status = 'Published' ORDER BY DisplayOrder, Title"));
}

function site_qualifications(): array
{
    return content_fetch('qualifications', static fn() => db_all("SELECT * FROM AboutQualifications WHERE Status = 'Published' ORDER BY DisplayOrder, Title"));
}

function site_engagements(): array
{
    return content_fetch('engagements', static fn() => db_all("SELECT * FROM SpeakingEngagements WHERE Status = 'Published' ORDER BY EventDate DESC, Title"));
}

/** Contact details and social links for public pages. Empty if the database is down. */
function site_settings(): array
{
    return content_fetch('settings', static fn() => db_row(
        'SELECT Tagline, Phone, Email, AddressLine1, AddressLine2, City, Province, PostalCode, Country, ShowAddress,
                Facebook, Instagram, LinkedIn, Pinterest FROM WebsiteSettings WHERE SettingID = 1'
    ));
}

/** One published service, or null. */
function site_service(string $slug): ?array
{
    foreach (site_services() as $service) {
        if ($service['Slug'] === $slug) {
            return $service;
        }
    }
    return null;
}

/** One published site page, or null. */
function site_page(string $slug): ?array
{
    $page = content_fetch('page_' . $slug, static fn() => db_row("SELECT * FROM SitePages WHERE Slug = ? AND Status = 'Published'", [$slug]) ?? []);
    return empty($page) ? null : $page;
}

/** Published projects with client permission, by category and/or featured. */
function site_projects(?string $category, bool $featuredOnly = false): array
{
    return content_fetch('projects', static function () use ($category, $featuredOnly): array {
        $sql    = "SELECT * FROM PortfolioProjects WHERE Status = 'Published' AND ClientPermission = 1";
        $params = [];
        if ($category !== null) {
            $sql .= ' AND PortfolioCategory = ?';
            $params[] = $category;
        }
        if ($featuredOnly) {
            $sql .= ' AND IsFeatured = 1';
        }
        return db_all($sql . ' ORDER BY DisplayOrder, Title', $params);
    });
}

function site_project(string $slug): ?array
{
    $project = content_fetch('projects', static fn() => db_row("SELECT * FROM PortfolioProjects WHERE Slug = ? AND Status = 'Published' AND ClientPermission = 1", [$slug]) ?? []);
    return empty($project) ? null : $project;
}

function site_project_images(int $projectId): array
{
    return content_fetch('projects', static fn() => db_all('SELECT * FROM ProjectImages WHERE ProjectID = ? ORDER BY DisplayOrder, ImageID', [$projectId]));
}

function site_collaborators(): array
{
    return content_fetch('collaborators', static fn() => db_all("SELECT * FROM Collaborators WHERE Status = 'Published' AND ConsentDate IS NOT NULL ORDER BY DisplayOrder, Name"));
}

/** Published sections of a page, keyed by SectionKey, each with its published items. */
function site_sections(string $page): array
{
    return content_fetch('sections_' . $page, static function () use ($page): array {
        $sections = [];
        foreach (db_all("SELECT * FROM PageSections WHERE PageKey = ? AND Status = 'Published' ORDER BY DisplayOrder", [$page]) as $section) {
            $section['items'] = db_all("SELECT * FROM PageSectionItems WHERE SectionID = ? AND Status = 'Published' ORDER BY DisplayOrder, ItemID", [$section['SectionID']]);
            $sections[$section['SectionKey']] = $section;
        }
        return $sections;
    });
}

// ---------------------------------------------------------------------------
// Rendering helpers
// ---------------------------------------------------------------------------

/** A service's own page, or its place on the Services page if it has none. */
function service_page_url(string $slug): string
{
    return is_file(ROOT_PATH . '/services/' . $slug . '.php') ? url('services/' . $slug . '.php') : url('services.php') . '#' . $slug;
}

/** A stored link as a safe address: site-relative paths go through url(). */
function content_url(?string $link): string
{
    $link = trim((string) $link);
    if ($link === '' || !link_is_safe($link)) {
        return '#';
    }
    return preg_match('/^[a-z][a-z0-9+.-]*:/i', $link) ? $link : url($link);
}

/** Link or button; nothing if the text or address is blank. $after is trusted markup. */
function content_link(?string $text, ?string $link, string $class, string $after = ''): string
{
    if (trim((string) $text) === '' || trim((string) $link) === '') {
        return '';
    }
    return '<a class="' . e($class) . '" href="' . e(content_url($link)) . '">' . e($text) . $after . '</a>';
}

/** Text as paragraphs, split on blank lines. The first can have its own class. */
function content_paragraphs(?string $text, string $firstClass = ''): string
{
    $html = '';
    foreach (preg_split('/\n\s*\n/', trim((string) $text)) ?: [] as $index => $paragraph) {
        if ($paragraph !== '') {
            $class = $index === 0 && $firstClass !== '' ? ' class="' . e($firstClass) . '"' : '';
            $html .= '<p' . $class . '>' . nl2br(e($paragraph), false) . '</p>';
        }
    }
    return $html;
}

/** A one-item-per-line field as a list of strings. */
function content_lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', explode("\n", (string) $text)), static fn(string $line): bool => $line !== ''));
}

/** Escaped text with the first occurrence of a phrase turned into a link. */
function content_with_link(?string $text, string $phrase, string $link): string
{
    $escaped = e($text);
    $phrase  = e($phrase);
    $anchor  = '<a href="' . e(content_url($link)) . '">' . $phrase . '</a>';
    $at      = strpos($escaped, $phrase);
    return $at === false ? $escaped : substr_replace($escaped, $anchor, $at, strlen($phrase));
}

/** aria-labelledby, only when the section has a heading to point at. */
function content_labelled(array $section, string $id): string
{
    return trim((string) ($section['Heading'] ?? '')) === '' ? '' : ' aria-labelledby="' . e($id) . '"';
}

function content_eyebrow(array $section): string
{
    return trim((string) ($section['Eyebrow'] ?? '')) === '' ? '' : '<p class="eyebrow">' . e($section['Eyebrow']) . '</p>';
}

function content_heading(array $section, string $id): string
{
    return trim((string) ($section['Heading'] ?? '')) === '' ? '' : '<h2 id="' . e($id) . '">' . e($section['Heading']) . '</h2>';
}

/** Centred section heading block. $intro replaces the section's own introduction. */
function content_section_header(array $section, string $id, ?string $intro = null): string
{
    $intro ??= $section['Introduction'] ?? '';
    return '<header class="section-header">' . content_eyebrow($section) . content_heading($section, $id)
         . (trim((string) $intro) === '' ? '' : '<p>' . e($intro) . '</p>') . '</header>';
}

/** Image frame: the photo if one is set, otherwise the decorative artwork. */
function content_frame(string $class, ?string $image, ?string $alt, string $decoration, string $size = 'web'): string
{
    if (trim((string) $image) === '') {
        return '<div class="' . e($class) . '" aria-hidden="true">' . $decoration . '</div>';
    }
    $src = link_is_safe((string) $image) ? image_url((string) $image, $size) : '#';
    return '<div class="' . e($class) . '"><img src="' . e($src) . '" alt="' . e($alt) . '"' . ($size === 'full' ? '' : ' loading="lazy"') . '></div>';
}

/**
 * Opening of an inner page header: a photo behind the heading when the
 * section has one, otherwise the botanical drawing. $edit is the block's
 * edit buttons (shown to signed-in staff only).
 */
function content_hero_open(array $header, string $edit = ''): string
{
    $image = trim((string) ($header['Image'] ?? ''));
    if ($image === '' || !link_is_safe($image)) {
        return '<section class="page-hero" aria-labelledby="page-title">' . $edit . botanical('botanical page-hero__botanical');
    }
    return '<section class="page-hero page-hero--image" aria-labelledby="page-title">' . $edit
         . '<img class="page-hero__image" src="' . e(image_url($image, 'full')) . '" alt="' . e($header['ImageAlt'] ?? '') . '">';
}

/** Inner page heading with breadcrumb. $crumbs is a list of [label, path] between Home and this page. */
function content_page_hero(array $header, array $crumbs, string $edit = ''): string
{
    $trail = '<li><a href="' . e(url('index.php')) . '">Home</a></li>';
    foreach ($crumbs as [$label, $path]) {
        $trail .= '<li><a href="' . e(url($path)) . '">' . e($label) . '</a></li>';
    }
    $heading = (string) ($header['Heading'] ?? '');
    $intro   = trim((string) ($header['Introduction'] ?? ''));

    return content_hero_open($header, $edit)
         . '<div class="container"><nav class="breadcrumb" aria-label="Breadcrumb"><ol>' . $trail
         . '<li aria-current="page">' . e($heading) . '</li></ol></nav>'
         . '<h1 id="page-title">' . e($heading) . '</h1>'
         . ($intro === '' ? '' : '<p class="page-hero__lead">' . e($intro) . '</p>')
         . '</div></section>';
}

/** A section heading followed by its items as cards. Used for any extra section on a page. */
function content_items_section(array $section, string $id, bool $peach, string $edit = ''): string
{
    $count = count($section['items']);
    $html  = '<section class="section' . ($peach ? ' section--peach' : '') . '"' . content_labelled($section, $id) . '>' . $edit . '<div class="container">'
           . content_section_header($section, $id);

    if ($count > 0) {
        $html .= '<ul class="journey' . ($count % 2 === 0 ? ' journey--2' : '') . '">';
        foreach ($section['items'] as $item) {
            $html .= '<li class="journey__step">'
                   . (empty($item['Icon']) ? '' : '<span class="journey__icon">' . icon((string) $item['Icon']) . '</span>')
                   . '<h3>' . e($item['Title']) . '</h3>' . content_paragraphs($item['Text']) . '</li>';
        }
        $html .= '</ul>';
    }

    return $html . '</div></section>';
}

/** Closing call-to-action banner. */
function content_closing(array $section, string $id, string $edit = ''): string
{
    return '<section class="section cta-banner"' . content_labelled($section, $id) . '>' . $edit . '<div class="container cta-banner__inner">'
         . content_heading($section, $id) . content_paragraphs($section['Introduction'] ?? '')
         . '<div class="btn-group btn-group--centred">'
         . content_link($section['ButtonText'] ?? '', $section['ButtonLink'] ?? '', 'btn btn--primary')
         . content_link($section['SecondaryButtonText'] ?? '', $section['SecondaryButtonLink'] ?? '', 'btn btn--outline')
         . '</div></div></section>';
}

function content_empty_state(array $section): string
{
    return '<div class="empty-state"><span class="empty-state__icon">' . icon('leaf') . '</span>'
         . '<p class="empty-state__title">' . e($section['Heading'] ?? '') . '</p>'
         . content_paragraphs($section['Introduction'] ?? '') . '</div>';
}

function content_project_tile(array $project, int $index): string
{
    $tones = ['green', 'gold', 'peach'];
    return '<li class="portfolio-tile">'
         . content_frame('art-frame art-frame--' . $tones[$index % 3], $project['FeaturedImage'] ?? '', $project['FeaturedImageAlt'] ?? '', icon('leaf', 'icon art-frame__icon'))
         . '<div class="portfolio-tile__body"><h3 class="portfolio-tile__title">'
         . '<a class="card__link" href="' . e(url('portfolio/project.php?slug=' . urlencode((string) $project['Slug']))) . '">' . e($project['Title']) . '</a></h3>'
         . (empty($project['Location']) ? '' : '<p>' . e($project['Location']) . '</p>')
         . '</div></li>';
}

/**
 * Plain text as a document: a block starting "## " is a heading, a block of
 * lines starting "- " is a list, anything else is a paragraph.
 */
function content_document(?string $text): string
{
    $html = '';
    foreach (preg_split('/\n\s*\n/', trim((string) $text)) ?: [] as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (str_starts_with($block, '## ')) {
            $html .= '<h2>' . e(substr($block, 3)) . '</h2>';
        } elseif (str_starts_with($block, '- ')) {
            $html .= '<ul>';
            foreach (explode("\n", $block) as $line) {
                $html .= '<li>' . e(ltrim(trim($line), '- ')) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>' . nl2br(e($block), false) . '</p>';
        }
    }
    return $html;
}

/** Stop with a page-not-found response, in the normal site layout. */
function content_not_found(): never
{
    http_response_code(404);
    $pageTitle       = 'Page not found';
    $pageDescription = SITE_TAGLINE;
    $noindex         = true;
    require INCLUDES_PATH . '/header.php';
    echo '<main id="main-content">', content_page_hero(['Heading' => 'Page not found'], []),
         '<section class="section"><div class="container"><div class="content-narrow"><p>Sorry, that page is not available.</p>',
         '<a class="text-link" href="', e(url('index.php')), '">Back to the home page</a></div></div></section></main>';
    require INCLUDES_PATH . '/footer.php';
    exit;
}
