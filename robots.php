<?php
/**
 * robots.txt: which parts of the site search engines may visit. .htaccess
 * serves this file at /robots.txt.
 *
 * Only the live site (APP_ENV=production) is open to search engines. A test
 * or staging copy asks them to stay away completely, so it can never show
 * up in Google next to the real site.
 *
 * AI: search tools that find and recommend websites (ChatGPT search,
 * Perplexity, Claude, Google's AI answers) follow the general rules, so they
 * may read the site; that is how it can appear in AI answers. Crawlers that
 * collect text and photos to train AI models are asked to stay away, unless
 * Website Settings → "Allow AI companies to train their models" is ticked.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';

header('Content-Type: text/plain; charset=UTF-8');

if (APP_ENV !== 'production') {
    echo "User-agent: *\nDisallow: /\n";
    exit;
}

$private = ['/admin/', '/login.php', '/logout.php', '/forgot-password.php', '/reset-password.php', '/newsletter.php', '/blog/search.php', '/scheduled-tasks.php'];

echo "User-agent: *\n";
foreach ($private as $path) {
    echo 'Disallow: ', BASE_URL, $path, "\n";
}

if (empty(site_search_settings()['AllowAITraining'])) {
    echo "\n# AI model training: please don't use this website's text and photos.\n";
    echo "# (AI search tools such as OAI-SearchBot, Claude-SearchBot and PerplexityBot are welcome.)\n";
    foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'meta-externalagent', 'Bytespider'] as $bot) {
        echo 'User-agent: ', $bot, "\n";
    }
    echo "Disallow: /\n";
}

if (mail_origin() !== '') {
    echo "\nSitemap: ", absolute_url('sitemap.xml'), "\n";
}
