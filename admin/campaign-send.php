<?php
/**
 * Sends the next batch of a campaign that is Sending. Called repeatedly by
 * the campaign page while it is open; returns progress as JSON.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/campaigns.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !is_logged_in() || !can('subscribers.edit') || !csrf_valid()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Please sign in again, then press Resume.']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
session_write_close(); // sending can take a few seconds; don't block other admin pages

@set_time_limit(120);
try {
    echo json_encode(['ok' => true] + campaign_process($id));
} catch (Throwable $error) {
    error_log('Campaign ' . $id . ' batch failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Sending paused because of an error. Press Resume to try again.']);
}
