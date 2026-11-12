<?php
/**
 * Old address for the blog archive: every post is listed on the blog page.
 */
require_once __DIR__ . '/../includes/config.php';

header('Location: ' . url('blog.php'), true, 301);
exit;
