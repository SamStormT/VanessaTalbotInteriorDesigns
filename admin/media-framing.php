<?php
/**
 * JSON: a Media Library picture's position and zoom in its frames.
 * GET ?path=   the picture's address, preview and current settings
 * POST path, fx, fy, zoom   saves them (fx and fy 0 to 100, zoom 1 to 3: a photo always fills its frame)
 * Used by "Adjust position and zoom" on image fields and the Media Library.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

$posting = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
media_guard_json($posting ? 'media.edit' : 'media.view');

$path = is_string($_REQUEST['path'] ?? null) ? trim($_REQUEST['path']) : '';
$row  = $path === '' ? null : db_row('SELECT MediaID, FilePath, FocusX, FocusY, Zoom, Width, Height FROM MediaLibrary WHERE FilePath = ?', [$path]);
if ($row === null) {
    media_json(['ok' => false, 'message' => 'That picture is not in the Media Library.'], 404);
}

if (!$posting) {
    media_json([
        'ok'   => true,
        'url'  => image_url($row['FilePath'], 'web'),
        'fx'   => (int) $row['FocusX'],
        'fy'   => (int) $row['FocusY'],
        'zoom'   => max(1.0, (float) $row['Zoom']),
        'width'  => (int) $row['Width'],
        'height' => (int) $row['Height'],
    ]);
}

$fx   = filter_var($_POST['fx'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$fy   = filter_var($_POST['fy'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
$zoom = filter_var($_POST['zoom'] ?? null, FILTER_VALIDATE_FLOAT);
if ($fx === false || $fy === false || $zoom === false || $zoom < 1 || $zoom > 3) {
    media_json(['ok' => false, 'message' => 'Those settings are out of range. Please try again.'], 422);
}
$zoom = round($zoom, 2);

db_query('UPDATE MediaLibrary SET FocusX = ?, FocusY = ?, Zoom = ?, LastEditedBy = ?, LastEditedDate = NOW() WHERE MediaID = ?',
    [$fx, $fy, $zoom, (int) (current_admin()['AdminUserID'] ?? 0) ?: null, (int) $row['MediaID']]);
activity_log('reframe', 'MediaLibrary', (int) $row['MediaID'], sprintf('Position %d%% across, %d%% down; zoom %d%%', $fx, $fy, (int) round($zoom * 100)));
media_json(['ok' => true]);
