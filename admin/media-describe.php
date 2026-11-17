<?php
/**
 * JSON: give a Media Library picture its description (alt text) when it has
 * none yet. Used by the article editor's "Picture details" step, so a photo
 * uploaded or chosen while writing is described once and that description
 * is reused everywhere. An existing description is never overwritten here;
 * change it on the Media Library screen.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    media_json(['ok' => false, 'message' => 'POST only.'], 405);
}
media_guard_json('media.create');

$path = is_string($_POST['path'] ?? null) ? trim($_POST['path']) : '';
$alt  = is_string($_POST['alt'] ?? null) ? trim(preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $_POST['alt'])) : '';

if ($path === '' || $alt === '' || !mb_check_encoding($alt, 'UTF-8') || mb_strlen($alt) > 255) {
    media_json(['ok' => false, 'message' => 'Please describe the picture in 255 characters or fewer.'], 422);
}

$updated = db_query("UPDATE MediaLibrary SET AltText = ? WHERE FilePath = ? AND (AltText IS NULL OR AltText = '')", [$alt, $path])->rowCount();
if ($updated > 0) {
    activity_log('describe', 'MediaLibrary', (int) db_value('SELECT MediaID FROM MediaLibrary WHERE FilePath = ?', [$path]), 'Description added from the article editor');
}
media_json(['ok' => true, 'updated' => $updated > 0]);
