<?php
/**
 * robots.txt: which parts of the site search engines may visit. .htaccess
 * serves this file at /robots.txt.
 *
 * Only the live site (APP_ENV=production) is open to search engines. A test
 * or staging copy asks them to stay away completely, so it can never show
 * up in Google next to the real site.
 */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=UTF-8');

if (APP_ENV !== 'production') {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}

echo "User-agent: *\n";
foreach (['/admin/', '/login.php', '/logout.php', '/forgot-password.php', '/reset-password.php', '/newsletter.php', '/blog/search.php'] as $path) {
    echo 'Disallow: ', BASE_URL, $path, "\n";
}
if (mail_origin() !== '') {
    echo "\nSitemap: ", absolute_url('sitemap.xml'), "\n";
}
