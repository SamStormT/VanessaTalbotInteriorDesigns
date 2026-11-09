<?php
/**
 * Sends visitors to Speaking Engagements.
 */
require_once __DIR__ . '/includes/config.php';

header('Location: ' . url('services/speaking-engagements.php'));
exit;
