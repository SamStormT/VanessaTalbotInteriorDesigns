<?php
/**
 * Homepage Content: view and edit. A single-row section, so it has no create or delete.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

cms_singleton_page('homepage');
