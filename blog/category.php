<?php
/**
 * Old address for a blog category: the blog page now filters by category.
 */
require_once __DIR__ . '/../includes/config.php';

$slug = is_string($_GET['slug'] ?? null) && preg_match('/^[a-z0-9-]+$/', $_GET['slug']) ? $_GET['slug'] : '';
header('Location: ' . url('blog.php' . ($slug !== '' ? '?category=' . $slug : '')), true, 301);
exit;
