<?php
/**
 * "Something went wrong": shown when a page fails (the error handler in
 * includes/config.php) and by Apache for its own server errors (.htaccess).
 * Deliberately needs nothing else to work: no database and no other files
 * except the stylesheet, so it still shows when the fault is elsewhere.
 * The details go to the error log, never to the visitor (in development they
 * are shown below the message).
 */
if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
}
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Something went wrong | Vanessa Talbot Interior Designs</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($base) ?>/assets/css/style.css">
</head>
<body class="layout-public">
<main id="main-content" class="error-page">
    <div class="error-page__card">
        <p class="error-page__brand">Vanessa Talbot <span>Interior Designs</span></p>
        <h1>Something went wrong</h1>
        <p>Sorry, this page couldn't be shown just now. Please try again in a few minutes.</p>
        <p><a class="btn btn--primary" href="<?= htmlspecialchars($base) ?>/">Go to the home page</a></p>
<?php if (isset($errorDetail) && is_string($errorDetail)): ?>
        <pre class="error-page__detail"><?= htmlspecialchars($errorDetail) ?></pre>
<?php endif; ?>
    </div>
</main>
</body>
</html>
