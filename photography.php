<?php
/**
 * Sends visitors to the photography gallery until the store is built.
 */
require_once __DIR__ . '/includes/config.php';

header('Location: ' . url('portfolio/photography-gallery.php'));
exit;
