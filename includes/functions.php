<?php
/**
 * Shared helper functions.
 * Only presentation helpers for now; data helpers come in a later phase.
 */

declare(strict_types=1);

/** Escape output for safe HTML rendering. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a site URL from a path relative to the project root. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Build a URL to a file in /assets. The file's last-changed time is added
 * (?v=...), so browsers fetch the new copy after every update instead of
 * running an old cached script or stylesheet.
 */
function asset(string $path): string
{
    $path    = ltrim($path, '/');
    $changed = @filemtime(ROOT_PATH . '/assets/' . $path);
    return url('assets/' . $path) . ($changed ? '?v=' . $changed : '');
}

/**
 * Brand logo.
 *
 * Uses the official logo file when it exists in /assets/images
 * (logo.svg for light backgrounds, logo-white.svg for dark ones).
 * Until then, renders a typographic lockup that mirrors the logo layout:
 * "Vanessa / Talbot" with "INTERIOR DESIGNS" set beside the second line.
 */
function brand_logo(string $variant = 'dark'): string
{
    $file = $variant === 'light' ? 'images/logo-white.svg' : 'images/logo.svg';

    if (is_file(ROOT_PATH . '/assets/' . $file)) {
        return '<img class="brand__logo" src="' . e(asset($file)) . '" alt="' . e(SITE_NAME) . '" width="220" height="72">';
    }

    return '<span class="brand__lockup brand__lockup--' . e($variant) . '">'
         . '<span class="brand__line">Vanessa</span>'
         . '<span class="brand__line">Talbot <span class="brand__sub">Interior <span>Designs</span></span></span>'
         . '</span>';
}

/**
 * Address of an uploaded image at a given size: 'full' (main, up to 1920px),
 * 'web' (up to 1200px) or 'thumb' (up to 400px). Images uploaded before
 * optimisation have no sizes, so they fall back to the saved path.
 */
function image_url(string $path, string $size = 'web'): string
{
    if ($size !== 'full') {
        $variant = (string) preg_replace('/(\.[A-Za-z0-9]+)$/', '-' . $size . '$1', $path);
        if ($variant !== $path && is_file(ROOT_PATH . '/' . $variant)) {
            return url($variant);
        }
    }
    return url($path);
}

/** Allow site-relative links and http(s), mailto or tel only. */
function link_is_safe(string $value): bool
{
    if (preg_match('/[\s<>"]/', $value) || str_starts_with($value, '//')) {
        return false;
    }
    if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $value, $match)) {
        return in_array(strtolower($match[1]), ['http', 'https', 'mailto', 'tel'], true);
    }
    return true;
}

/** Current request path relative to BASE_URL, e.g. "services/workshops.php". */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }
    $path = ltrim($path, '/');
    return $path === '' ? 'index.php' : $path;
}

/**
 * True when the current page matches $path, or sits inside the $path section
 * (e.g. "services.php" is active for "services/workshops.php").
 */
function is_active(string $path): bool
{
    $current = current_path();
    if ($current === $path) {
        return true;
    }
    $section = preg_replace('/\.php$/', '', $path);
    return $section !== 'index' && str_starts_with($current, $section . '/');
}
