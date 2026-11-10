<?php
/**
 * Optimise images uploaded before automatic optimisation existed (command line only).
 *
 *   php database/optimise-media.php
 *
 * For each library image without an original on record, the current file is
 * kept as the private original, then the main, web and thumbnail versions are
 * written. The saved path does not change, so every page that uses the image
 * keeps working. Safe to run again: optimised images are skipped.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once INCLUDES_PATH . '/database.php';
require_once INCLUDES_PATH . '/media-optimise.php';

$rows = db_all('SELECT MediaID, FilePath, FileType, FileSize, Width, Height FROM MediaLibrary WHERE OriginalPath IS NULL ORDER BY MediaID');
$done = $failed = 0;

foreach ($rows as $row) {
    $main     = ROOT_PATH . '/' . $row['FilePath'];
    $original = media_original_path($row['FilePath']);

    if (!is_file($main)) {
        echo "Skipped (file missing): {$row['FilePath']}", PHP_EOL;
        continue;
    }

    try {
        media_make_folder(ROOT_PATH . '/' . $original);
        if (!is_file(ROOT_PATH . '/' . $original)) {
            media_copy($main, ROOT_PATH . '/' . $original);
        }
        $result = media_optimise(ROOT_PATH . '/' . $original, $row['FileType'], $main);
        $status = $result['status'];
        $done++;
    } catch (RuntimeException $error) {
        error_log('Optimising ' . $row['FilePath'] . ' failed: ' . $error->getMessage());
        $result = ['width' => (int) $row['Width'], 'height' => (int) $row['Height'], 'size' => (int) $row['FileSize']];
        $status = 'Failed';
        $failed++;
    }

    db_query(
        'UPDATE MediaLibrary SET OriginalPath = ?, OriginalWidth = ?, OriginalHeight = ?, OriginalFileSize = ?,
                Width = ?, Height = ?, FileSize = ?, Optimisation = ? WHERE MediaID = ?',
        [$original, (int) $row['Width'], (int) $row['Height'], (int) $row['FileSize'],
         $result['width'], $result['height'], $result['size'], $status, $row['MediaID']]
    );
    echo "$status: {$row['FilePath']}", PHP_EOL;
}

echo "Done. Optimised $done, could not optimise $failed, checked " . count($rows) . '.', PHP_EOL;
