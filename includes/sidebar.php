<?php
/**
 * Admin sidebar. Items the signed-in role can't use are not shown; each page
 * also checks permission on the server.
 */

$adminNav = [
    '' => [
        ['label' => 'Dashboard', 'path' => 'admin/dashboard.php', 'can' => 'dashboard.view'],
    ],
    'Website Content' => [
        ['label' => 'Pages (what is where)', 'path' => 'admin/pages.php',              'can' => 'dashboard.view'],
        ['label' => 'Homepage Content',     'path' => 'admin/homepage-content.php',     'can' => 'homepage.view'],
        ['label' => 'About Content',        'path' => 'admin/about-content.php',        'can' => 'about.view'],
        ['label' => 'About Values',         'path' => 'admin/about-values.php',         'can' => 'about_items.view'],
        ['label' => 'Qualifications',       'path' => 'admin/qualifications.php',       'can' => 'about_items.view'],
        ['label' => 'Page Sections',        'path' => 'admin/page-sections.php',        'can' => 'page_sections.view'],
        ['label' => 'Services',             'path' => 'admin/services.php',             'can' => 'services.view'],
        ['label' => 'Speaking Engagements', 'path' => 'admin/speaking-engagements.php', 'can' => 'speaking.view'],
        ['label' => 'Testimonials',         'path' => 'admin/testimonials.php',         'can' => 'testimonials.view'],
        ['label' => 'Legal Pages',          'path' => 'admin/site-pages.php',           'can' => 'site_pages.view'],
    ],
    'Portfolio & Blog' => [
        ['label' => 'Portfolio Projects', 'path' => 'admin/portfolio-projects.php', 'can' => 'portfolio.view'],
        ['label' => 'Collaborators',      'path' => 'admin/collaborators.php',      'can' => 'collaborators.view'],
        ['label' => 'Blogs',              'path' => 'admin/blogs.php',              'can' => 'blogs.view'],
    ],
    'Photography' => [
        ['label' => 'Photography Products', 'path' => 'admin/photography-products.php', 'can' => 'products.view'],
        ['label' => 'Photography Orders',   'path' => 'admin/photography-orders.php',   'can' => 'orders.view'],
    ],
    'Audience' => [
        ['label' => 'Subscribers',      'path' => 'admin/subscribers.php', 'can' => 'subscribers.view'],
        ['label' => 'Contact Messages', 'path' => 'admin/enquiries.php',   'can' => 'messages.view'],
    ],
    'Library' => [
        ['label' => 'Media Library', 'path' => 'admin/media-library.php', 'can' => 'media.view'],
    ],
    'System' => [
        ['label' => 'Website Settings', 'path' => 'admin/settings.php',    'can' => 'settings.view'],
        ['label' => 'Users',            'path' => 'admin/admin-users.php', 'can' => 'users.view'],
    ],
];

$activeNav = $activeNav ?? current_path();
?>
<aside id="admin-sidebar" class="admin-sidebar">
    <a class="admin-sidebar__brand" href="<?= e(url('admin/dashboard.php')) ?>">
        <span class="brand__name">Vanessa Talbot</span>
        <span class="admin-sidebar__label">Admin</span>
    </a>
    <nav aria-label="Admin navigation">
<?php foreach ($adminNav as $group => $links):
    $links = array_filter($links, static fn(array $link): bool => can($link['can']));
    if ($links === []) {
        continue;
    }
?>
<?php if ($group !== ''): ?>
        <p class="admin-sidebar__group"><?= e($group) ?></p>
<?php endif; ?>
        <ul class="admin-sidebar__list">
<?php foreach ($links as $link): ?>
            <li><a class="admin-sidebar__link<?= $activeNav === $link['path'] ? ' is-active' : '' ?>" href="<?= e(url($link['path'])) ?>"<?= $activeNav === $link['path'] ? ' aria-current="page"' : '' ?>><?= e($link['label']) ?></a></li>
<?php endforeach; ?>
        </ul>
<?php endforeach; ?>
    </nav>
</aside>
