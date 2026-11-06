<?php
/**
 * Admin sidebar navigation.
 */

$adminNav = [
    'Overview' => [
        ['label' => 'Dashboard',     'path' => 'admin/dashboard.php'],
        ['label' => 'Enquiries',     'path' => 'admin/enquiries.php'],
        ['label' => 'Subscribers',   'path' => 'admin/subscribers.php'],
    ],
    'Content' => [
        ['label' => 'Blog Posts',          'path' => 'admin/blogs.php'],
        ['label' => 'Portfolio Projects',  'path' => 'admin/portfolio-projects.php'],
        ['label' => 'Speaking Engagements','path' => 'admin/speaking-engagements.php'],
        ['label' => 'Testimonials',        'path' => 'admin/testimonials.php'],
        ['label' => 'Media Library',       'path' => 'admin/media-library.php'],
    ],
    'Photography Store' => [
        ['label' => 'Products', 'path' => 'admin/photography-products.php'],
        ['label' => 'Orders',   'path' => 'admin/photography-orders.php'],
    ],
    'System' => [
        ['label' => 'Admin Users', 'path' => 'admin/admin-users.php'],
        ['label' => 'Settings',    'path' => 'admin/settings.php'],
    ],
];
?>
<aside id="admin-sidebar" class="admin-sidebar">
    <a class="admin-sidebar__brand" href="<?= e(url('admin/dashboard.php')) ?>">
        <span class="brand__name">Vanessa Talbot</span>
        <span class="admin-sidebar__label">Admin</span>
    </a>
    <nav aria-label="Admin navigation">
<?php foreach ($adminNav as $group => $links): ?>
        <p class="admin-sidebar__group"><?= e($group) ?></p>
        <ul class="admin-sidebar__list">
<?php foreach ($links as $link): ?>
            <li><a class="admin-sidebar__link<?= is_active($link['path']) ? ' is-active' : '' ?>" href="<?= e(url($link['path'])) ?>"><?= e($link['label']) ?></a></li>
<?php endforeach; ?>
        </ul>
<?php endforeach; ?>
    </nav>
</aside>
