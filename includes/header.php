<?php
/**
 * Shared document head + opening layout.
 *
 * Variables a page may set before including this file:
 *   $pageTitle        string  Page name shown in the <title>
 *   $pageDescription  string  Meta description
 *   $layout           string  'public' (default) or 'admin'
 *   $noindex          bool    Keep this page out of search engines
 *   $activeNav        string  Admin only: sidebar item to highlight
 */

$pageTitle       = $pageTitle ?? '';
$pageDescription = $pageDescription ?? SITE_TAGLINE;
$layout          = $layout ?? 'public';

$fullTitle = $pageTitle !== '' ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME . ' | ' . SITE_TAGLINE;
?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#243A39">
<?php if ($layout === 'admin' || !empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
<?php else: ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
<?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Allison&family=Gilda+Display&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php if ($layout === 'admin' || !empty($noindex)): ?>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/responsive.css')) ?>">
</head>
<body class="layout-<?= e($layout) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<?php
if ($layout === 'admin') {
    echo '<div class="admin-shell">';
    require INCLUDES_PATH . '/sidebar.php';
    echo '<div class="admin-main">';
    require INCLUDES_PATH . '/topbar.php';
} else {
    require INCLUDES_PATH . '/navbar.php';
}
