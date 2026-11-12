<?php
/**
 * Blog Categories: add and edit. Deleting a category keeps its posts; they
 * simply have no category until another is chosen.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

cms_record_edit_page('blog_categories');
