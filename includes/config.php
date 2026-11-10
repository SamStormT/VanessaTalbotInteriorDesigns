<?php
/**
 * Vanessa Talbot Interior Designs
 * Site configuration.
 *
 * Secrets never live in this file. Values come from real environment
 * variables or from a private .env file (see .env.example), looked up in:
 *   1. the path in the ENV_FILE environment variable
 *   2. the folder above the website (recommended on the live server)
 *   3. the project folder (convenient for local development)
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');

/** Read a configuration value from the environment or the private .env file. */
function env_value(string $key, string $default = ''): string
{
    static $file = null;

    if ($file === null) {
        $file = [];
        $candidates = [(string) getenv('ENV_FILE'), dirname(ROOT_PATH) . '/.env', ROOT_PATH . '/.env'];
        foreach ($candidates as $path) {
            if ($path === '' || !is_file($path)) {
                continue;
            }
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $file[trim($name)] = trim(trim($value), "\"'");
            }
            break;
        }
    }

    $real = getenv($key);
    return $real !== false ? $real : ($file[$key] ?? $default);
}

// ---------------------------------------------------------------------------
// Environment and error handling
// ---------------------------------------------------------------------------
// Defaults to production, so a missing .env can never expose error details.
define('APP_ENV', env_value('APP_ENV', 'production') === 'development' ? 'development' : 'production');
define('LOG_PATH', env_value('LOG_PATH', ROOT_PATH . '/logs'));

error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(LOG_PATH) || @mkdir(LOG_PATH, 0750, true)) {
    ini_set('error_log', LOG_PATH . '/php-error.log');
}

date_default_timezone_set('Africa/Johannesburg');

// Log every unhandled error and show visitors a plain message with no details.
set_exception_handler(static function (Throwable $error): void {
    error_log(sprintf('%s: %s in %s:%d', get_class($error), $error->getMessage(), $error->getFile(), $error->getLine()));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="en-ZA"><head><meta charset="UTF-8"><title>Something went wrong</title></head>'
       . '<body style="font-family:sans-serif;padding:2rem"><h1>Something went wrong</h1>'
       . '<p>Please try again in a few minutes.</p>'
       . (APP_ENV === 'development' ? '<pre>' . htmlspecialchars((string) $error) . '</pre>' : '')
       . '</body></html>';
});

// ---------------------------------------------------------------------------
// Site identity
// ---------------------------------------------------------------------------
define('SITE_NAME', 'Vanessa Talbot Interior Designs');
define('SITE_TAGLINE', 'The Balance of Art, Science and Nature in Spaces');

// Leave empty when the site is served from the domain root (e.g. on Xneelo).
// Set to '/subfolder' if served from a subdirectory locally.
define('BASE_URL', rtrim(env_value('BASE_URL'), '/'));

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME'));
define('DB_USER', env_value('DB_USER'));
define('DB_PASS', env_value('DB_PASS'));
define('DB_CHARSET', 'utf8mb4');

// The site's public address, such as https://www.example.co.za, with no
// trailing slash. Links in emails are built from this, never from the
// visitor's request, so a forged Host header can't redirect a reset link.
define('APP_URL', rtrim(env_value('APP_URL'), '/'));

// ---------------------------------------------------------------------------
// Mail (PHPMailer; see includes/mail.php)
// ---------------------------------------------------------------------------
// MAIL_MAILER: smtp (default) sends through the mail server; log writes each
// email to logs/mail/ instead, for local development.
define('MAIL_MAILER', env_value('MAIL_MAILER', 'smtp') === 'log' ? 'log' : 'smtp');
define('MAIL_HOST', env_value('MAIL_HOST'));
define('MAIL_PORT', (int) env_value('MAIL_PORT', '587'));
// tls (STARTTLS, usually port 587), ssl (usually port 465) or none.
define('MAIL_ENCRYPTION', env_value('MAIL_ENCRYPTION', MAIL_PORT === 465 ? 'ssl' : 'tls'));
define('MAIL_USERNAME', env_value('MAIL_USERNAME'));
define('MAIL_PASSWORD', env_value('MAIL_PASSWORD'));
define('MAIL_FROM_ADDRESS', env_value('MAIL_FROM_ADDRESS'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', SITE_NAME));

// ---------------------------------------------------------------------------
// PayFast (not used yet; a later phase)
// ---------------------------------------------------------------------------
define('PAYFAST_SANDBOX', env_value('PAYFAST_SANDBOX', 'true') !== 'false');
define('PAYFAST_MERCHANT_ID', env_value('PAYFAST_MERCHANT_ID'));
define('PAYFAST_MERCHANT_KEY', env_value('PAYFAST_MERCHANT_KEY'));
define('PAYFAST_PASSPHRASE', env_value('PAYFAST_PASSPHRASE'));

require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/icons.php';
