<?php
/**
 * "Page not found" for any address that doesn't exist (.htaccess
 * ErrorDocument 404), in the website's own design, with links back in.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

content_not_found();
