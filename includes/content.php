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
function content_frame(string $class, ?string $image, ?string $alt, string $decoration): string
{
    if (trim((string) $image) === '') {
        return '<div class="' . e($class) . '" aria-hidden="true">' . $decoration . '</div>';
    }
    return '<div class="' . e($class) . '"><img src="' . e(content_url($image)) . '" alt="' . e($alt) . '" loading="lazy"></div>';
}
