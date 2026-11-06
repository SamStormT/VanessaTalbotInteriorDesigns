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

/** Build a URL to a file in /assets. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
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
