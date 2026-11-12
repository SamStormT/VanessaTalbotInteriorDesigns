<?php
/**
 * New Blog Post: the editor handles both new and existing posts.
 */
require_once __DIR__ . '/../includes/config.php';

header('Location: ' . url('admin/blog-edit.php'), true, 301);
exit;
