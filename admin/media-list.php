<?php
/**
 * Searchable list of library images for the "Choose from library" window.
 * Answers in JSON.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

media_guard_json('media.view');

const MEDIA_PICKER_PAGE = 24;

$query  = is_string($_GET['q'] ?? null) ? mb_substr($_GET['q'], 0, 100) : '';
$offset = max(0, (int) ($_GET['offset'] ?? 0));

media_json([
    'ok'    => true,
    'items' => array_map('media_public', media_search($query, MEDIA_PICKER_PAGE, $offset)),
    'total' => media_count($query),
]);
