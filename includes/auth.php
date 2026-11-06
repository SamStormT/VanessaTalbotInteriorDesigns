<?php
/**
 * Authentication (placeholder).
 *
 * Until real authentication is implemented, is_logged_in() always returns
 * false, so every admin page redirects to login.php. This keeps the admin
 * area closed on the live server while it is still a placeholder.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    // Phase 2+: check a verified admin session.
    return false;
}

function require_admin(): void
{
    if (!is_logged_in()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}
