<?php
/**
 * Sign out. POST with a CSRF token only, so a link on another site can't sign
 * someone out.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_valid()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

auth_logout();
flash_set('success', 'You have been signed out.');
header('Location: ' . url('login.php'));
exit;
