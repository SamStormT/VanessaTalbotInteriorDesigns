<?php
/**
 * Privacy-friendly visitor statistics: how many times each page was viewed
 * each day, and which other websites people came from. Nothing about the
 * visitor is kept: no cookies, no IP address, no browser details, no
 * tracking across pages or days. So no cookie notice is needed and nothing
 * goes to a third party. (A person reading three pages counts as three views.)
 *
 * Not counted: signed-in staff, search engine and link-preview robots,
 * browsers fetching a page in advance, pages kept out of search engines
 * (sign-in, previews, "not found"), and anything other than a normal page.
 * Shown under Marketing → Statistics (admin/statistics.php). Kept for
 * Website Settings → Privacy → Visitor statistics months.
 */

declare(strict_types=1);

/** Robots and tools that aren't people reading the page. */
const STATS_ROBOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|slack|embedly|curl|wget|python|java\/|go-http|headless|lighthouse|pingdom|uptime|monitor|feed|httpclient|okhttp|axios|node-fetch|scrapy/i';

/** Count this page view (called at the end of every public page, see footer.php). */
function stats_record(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || http_response_code() !== 200) {
        return;
    }
    $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($agent === '' || preg_match(STATS_ROBOTS, $agent)) {
        return;
    }
    $purpose = strtolower((string) ($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_X_MOZ'] ?? ''));
    if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
        return;
    }

    try {
        $path = stats_path();
        db_query('INSERT INTO PageViews (ViewDate, Path, Views) VALUES (CURDATE(), ?, 1) ON DUPLICATE KEY UPDATE Views = Views + 1', [$path]);
        if (($source = stats_source()) !== null) {
            db_query('INSERT INTO PageReferrers (ViewDate, Source, Views) VALUES (CURDATE(), ?, 1) ON DUPLICATE KEY UPDATE Views = Views + 1', [$source]);
        }
    } catch (Throwable $error) {
        // Statistics must never break a page.
    }
}

/** This page, as counted: the file, plus the article, project or category it shows. */
function stats_path(): string
{
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $path   = ltrim(BASE_URL !== '' && str_starts_with($script, BASE_URL) ? substr($script, strlen(BASE_URL)) : $script, '/');
    foreach (['slug', 'category'] as $key) {
        if (is_string($_GET[$key] ?? null) && preg_match('/^[a-z0-9-]{1,150}$/', $_GET[$key])) {
            return $path . '?' . $key . '=' . $_GET[$key];
        }
    }
    return mb_substr($path === '' ? 'index.php' : $path, 0, 255);
}

/** The other website the visitor came from (just its name), or null. */
function stats_source(): ?string
{
    $referrer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $host     = strtolower((string) parse_url($referrer, PHP_URL_HOST));
    $own      = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $own      = (string) preg_replace('/:\d+$/', '', $own);
    if ($host === '' || $host === $own || $host === 'www.' . $own || 'www.' . $host === $own) {
        return null;
    }
    return mb_substr((string) preg_replace('/^(www|m|l|lm)\./', '', $host), 0, 100);
}

/** What kind of website a source is, for grouping. */
function stats_source_kind(string $source): string
{
    return match (true) {
        (bool) preg_match('/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|brave|yandex|baidu)\./', $source) => 'Search engine',
        (bool) preg_match('/(chatgpt\.com|openai\.com|perplexity\.ai|claude\.ai|copilot\.microsoft\.com|gemini\.google\.com|you\.com|phind\.com)$/', $source) => 'AI assistant',
        (bool) preg_match('/(facebook|instagram|linkedin|pinterest|twitter|x\.com|t\.co|tiktok|youtube|reddit|threads|wa\.me|whatsapp)/', $source) => 'Social media',
        default => 'Other website',
    };
}
