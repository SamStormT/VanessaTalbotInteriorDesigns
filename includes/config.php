<?php
/**
 * Vanessa Talbot Interior Designs
 * Site configuration (placeholder)
 *
 * Real credentials (database, SMTP, PayFast) must NOT be committed.
 * They will be loaded from a private config file outside the web root in a later phase.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Environment
// ---------------------------------------------------------------------------
define('APP_ENV', 'development'); // 'development' | 'production'

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

date_default_timezone_set('Africa/Johannesburg');

// ---------------------------------------------------------------------------
// Site identity
// ---------------------------------------------------------------------------
define('SITE_NAME', 'Vanessa Talbot Interior Designs');
define('SITE_TAGLINE', 'The Balance of Art, Science and Nature in Spaces');

// ---------------------------------------------------------------------------
// Paths & URLs
// ---------------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// Leave empty when the site is served from the domain root (e.g. on Xneelo).
// Set to '/subfolder' if served from a subdirectory locally.
define('BASE_URL', '');

// ---------------------------------------------------------------------------
// Database (placeholder — implemented in a later phase)
// ---------------------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------------
// Mail (placeholder — PHPMailer, implemented in a later phase)
// ---------------------------------------------------------------------------
define('MAIL_HOST', '');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_ADDRESS', '');
define('MAIL_FROM_NAME', SITE_NAME);

// ---------------------------------------------------------------------------
// PayFast (placeholder — implemented in a later phase)
// ---------------------------------------------------------------------------
define('PAYFAST_SANDBOX', true);
define('PAYFAST_MERCHANT_ID', '');
define('PAYFAST_MERCHANT_KEY', '');
define('PAYFAST_PASSPHRASE', '');

require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/icons.php';
