<?php
/**
 * Shared document head + opening layout.
 *
 * Variables a page may set before including this file:
 *   $pageTitle        string  Page name shown in the <title>
 *   $pageDescription  string  Meta description
 *   $layout           string  'public' (default) or 'admin'
 *   $noindex          bool    Keep this page out of search engines
 *   $pageImage        string  Image for link previews when shared (an uploads/ path);
 *                             the default sharing image is used when it is empty
 *   $pageImageAlt     string  Description of that image
 *   $pagePath         string  This page's own address, for search engines and sharing,
 *                             for example 'blog/blog-post.php?slug=...'. Defaults to
 *                             the page's file, without any ?filters
 *   $pageType         string  'website' (default) or 'article' (a blog post)
 *   $articleDate      string  Article only: date first published
 *   $structuredData   array   Extra schema.org data for this page (see includes/seo.php)
 *   $activeNav        string  Admin only: sidebar item to highlight
 */

$pageTitle       = $pageTitle ?? '';
$pageDescription = $pageDescription ?? SITE_TAGLINE;
$layout          = $layout ?? 'public';

$fullTitle = $pageTitle !== '' ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE;

// Sharing and search details for public pages. Full addresses need APP_URL
// (the live https address), so without it these tags are left out.
$share = null;
if ($layout !== 'admin' && empty($noindex) && mail_origin() !== '') {
    if (!isset($pagePath)) {
        $shareScript = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $pagePath = ltrim(BASE_URL !== '' && str_starts_with($shareScript, BASE_URL) ? substr($shareScript, strlen(BASE_URL)) : $shareScript, '/');
    }
    $pagePath = $pagePath === 'index.php' ? '' : $pagePath;
    $shareImage = !empty($pageImage) && link_is_safe((string) $pageImage)
        ? ['url' => image_url((string) $pageImage, 'web'), 'alt' => (string) ($pageImageAlt ?? ''), 'width' => null, 'height' => null]
        : (function_exists('site_share_image') ? site_share_image() : null);
    $share = [
        'url'   => absolute_url($pagePath),
        'type'  => ($pageType ?? 'website') === 'article' ? 'article' : 'website',
        'image' => $shareImage === null ? null : ['url' => mail_origin() . $shareImage['url']] + $shareImage,
    ];
    unset($shareScript, $shareImage);
}

// Signed-in staff see a bar and Edit buttons on the public website. Checked
// here, before any output, because it may open their session.
$staff = $layout !== 'admin' && function_exists('staff_viewer') ? staff_viewer() : null;
?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#243A39">
<?php
// Google Search Console and Bing Webmaster Tools ownership checks (Website
// Settings → Search engines and AI assistants), on public pages only.
if ($layout !== 'admin' && function_exists('site_search_settings')) {
    foreach (['google-site-verification' => 'GoogleVerification', 'msvalidate.01' => 'BingVerification'] as $tag => $field) {
        $code = trim((string) (site_search_settings()[$field] ?? ''));
        if ($code !== '' && preg_match('/^[A-Za-z0-9_\-]{6,100}$/', $code)) {
            echo '    <meta name="', $tag, '" content="', e($code), '">', "\n";
        }
    }
}
?>
<?php if ($layout === 'admin'): ?>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="media-upload-url" content="<?= e(url('admin/media-upload.php')) ?>">
    <meta name="media-list-url" content="<?= e(url('admin/media-list.php')) ?>">
    <meta name="media-describe-url" content="<?= e(url('admin/media-describe.php')) ?>">
<?php endif; ?>
<?php if ($layout === 'admin' || !empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
<?php else: ?>
<?php if ($share !== null): ?>
    <link rel="canonical" href="<?= e($share['url']) ?>">
    <meta property="og:type" content="<?= e($share['type']) ?>">
    <meta property="og:url" content="<?= e($share['url']) ?>">
    <meta property="og:locale" content="en_ZA">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:title" content="<?= e($pageTitle !== '' ? $pageTitle : $fullTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
<?php if ($share['image'] !== null): ?>
    <meta property="og:image" content="<?= e($share['image']['url']) ?>">
<?php if ($share['image']['width']): ?>
    <meta property="og:image:width" content="<?= (int) $share['image']['width'] ?>">
    <meta property="og:image:height" content="<?= (int) $share['image']['height'] ?>">
<?php endif; ?>
<?php if ($share['image']['alt'] !== ''): ?>
    <meta property="og:image:alt" content="<?= e($share['image']['alt']) ?>">
<?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<?php
    // Structured data for search engines and AI assistants (includes/seo.php): the
    // business on its main pages, the website on the home page, the designer on
    // About, and whatever the page adds itself (an article or a service).
    require_once INCLUDES_PATH . '/seo.php';
    if (in_array($pagePath, ['', 'about.php', 'contact.php', 'book-consultation.php'], true)) {
        echo '    ', seo_script(seo_business());
    }
    if ($pagePath === '') {
        echo '    ', seo_script(seo_website());
    }
    if ($pagePath === 'about.php') {
        echo '    ', seo_script(seo_person());
    }
    if (isset($structuredData) && is_array($structuredData)) {
        echo '    ', seo_script($structuredData);
    }
?>
<?php if ($share['type'] === 'article' && !empty($articleDate)): ?>
    <meta property="article:published_time" content="<?= e(date('c', strtotime((string) $articleDate))) ?>">
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

    <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="48x48">
    <link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?= e(asset('images/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(url('site.webmanifest')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allison&family=Gilda+Display&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php if ($layout === 'admin' || !empty($noindex)): ?>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
</head>
<body class="layout-<?= e($layout) ?><?= $staff ? ' is-staff' : '' ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php if ($staff): $mapKey = page_map_current(); ?>
<div class="staff-bar" role="region" aria-label="Website editing">
    <span>Signed in as <strong><?= e($staff['Name']) ?></strong></span>
    <a href="<?= e(url('admin/dashboard.php')) ?>">Dashboard</a>
<?php if ($mapKey !== null): ?>
    <a href="<?= e(url('admin/pages.php') . '#page-' . $mapKey) ?>">Everything on this page</a>
<?php endif; ?>
    <button type="button" class="staff-bar__toggle" data-edit-toggle aria-pressed="true">Hide edit buttons</button>
</div>
<?php endif; ?>
<?php
if ($layout === 'admin') {
    echo '<div class="admin-shell">';
    require INCLUDES_PATH . '/sidebar.php';
    echo '<div class="admin-main">';
    require INCLUDES_PATH . '/topbar.php';
} else {
    require INCLUDES_PATH . '/navbar.php';
}
