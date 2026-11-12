<?php
/**
 * "Preview" in the article editor: returns formatted text as HTML (JSON).
 * Nothing is saved.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/rich-text.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !is_logged_in() || !can('dashboard.view') || !csrf_valid()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Please sign in again.']);
    exit;
}

$text = is_string($_POST['text'] ?? null) ? mb_substr($_POST['text'], 0, 100000) : '';
$html = rich_render($text);

echo json_encode(['ok' => true, 'html' => $html !== '' ? $html : '<p><em>Nothing to preview yet.</em></p>']);
