<?php
/**
 * sitemap.xml: every public page, for search engines. .htaccess serves this
 * file at /sitemap.xml. Only published content is listed, so drafts,
 * scheduled posts and projects without the client's permission never appear.
 *
 * Needs APP_URL (the live https address) in .env: search engines need full
 * addresses.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/blog.php';

if (mail_origin() === '') {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "The sitemap needs APP_URL to be set.\n";
    exit;
}

/** @var array<int, array{0: string, 1: ?string}> $pages  [address, last changed] */
$pages = [];
foreach (['', 'about.php', 'services.php', 'portfolio.php', 'blog.php', 'contact.php'] as $page) {
    $pages[] = [$page, null];
}

foreach (site_services() as $service) {
    if (is_file(__DIR__ . '/services/' . $service['Slug'] . '.php')) {
        $pages[] = ['services/' . $service['Slug'] . '.php', $service['LastEditedDate'] ?? null];
    }
}

foreach (['biophilic-interior-design', 'colour-psychology', 'photography-gallery'] as $category) {
    $pages[] = ['portfolio/' . $category . '.php', null];
}
foreach (site_projects(null) as $project) {
    $pages[] = ['portfolio/project.php?slug=' . rawurlencode((string) $project['Slug']), $project['LastEditedDate'] ?? null];
}
if (site_collaborators() !== []) {
    $pages[] = ['portfolio/collaborators.php', null];
}

foreach (blog_sitemap_posts() as $post) {
    $pages[] = ['blog/blog-post.php?slug=' . rawurlencode((string) $post['Slug']), $post['UpdatedDate'] ?? $post['PublishedDate']];
}

foreach (['privacy-policy', 'terms-and-conditions', 'copyright'] as $legal) {
    if (site_page($legal) !== null) {
        $pages[] = [$legal . '.php', null];
    }
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($pages as [$page, $changed]) {
    echo '  <url><loc>', e(absolute_url($page)), '</loc>';
    if ($changed) {
        echo '<lastmod>', e(date('Y-m-d', strtotime((string) $changed))), '</lastmod>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
