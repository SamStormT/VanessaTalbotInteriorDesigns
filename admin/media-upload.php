<?php
/**
 * Receives one image from a drop zone or file picker and stores it in the
 * Media Library. Answers in JSON.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    media_json(['ok' => false, 'message' => 'Upload only.'], 405);
}

// PHP discards the whole request when it is over its limit, CSRF token included.
if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    media_json(['ok' => false, 'message' => media_too_large_message()], 413);
}

media_guard_json('media.create');

try {
    $category = is_string($_POST['category'] ?? null) ? trim($_POST['category']) : '';
    $row      = media_store_upload($_FILES['file'] ?? [], $category);
    media_json(['ok' => true, 'item' => media_public($row)]);
} catch (RuntimeException $error) {
    media_json(['ok' => false, 'message' => $error->getMessage()], 422);
}
