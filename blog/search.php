<?php
/**
 * Old address for blog search: the blog page now has search built in.
 */
require_once __DIR__ . '/../includes/config.php';

$query = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
header('Location: ' . url('blog.php' . ($query !== '' ? '?q=' . rawurlencode($query) : '')), true, 301);
exit;
