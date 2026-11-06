<?php
/**
 * Public site navigation.
 */

$navItems = [
    ['label' => 'Home',        'path' => 'index.php'],
    ['label' => 'About',       'path' => 'about.php'],
    ['label' => 'Services',    'path' => 'services.php', 'children' => [
        ['label' => 'Biophilic Design',     'path' => 'services/biophilic-design.php'],
        ['label' => 'Colour Psychology',    'path' => 'services/colour-psychology.php'],
        ['label' => 'Photography',          'path' => 'services/photography-services.php'],
        ['label' => 'Workshops',            'path' => 'services/workshops.php'],
        ['label' => 'Speaking Engagements', 'path' => 'services/speaking-engagements.php'],
        ['label' => 'Consultations',        'path' => 'services/consultations.php'],
    ]],
    ['label' => 'Portfolio',   'path' => 'portfolio.php', 'children' => [
        ['label' => 'Biophilic Interior Design', 'path' => 'portfolio/biophilic-interior-design.php'],
        ['label' => 'Colour Psychology',         'path' => 'portfolio/colour-psychology.php'],
        ['label' => 'Photography Gallery',       'path' => 'portfolio/photography-gallery.php'],
        ['label' => 'Collaborators',             'path' => 'portfolio/collaborators.php'],
    ]],
    ['label' => 'Photography', 'path' => 'photography.php'],
    ['label' => 'Blog',        'path' => 'blog.php'],
    ['label' => 'Speaking',    'path' => 'speaking.php'],
    ['label' => 'Contact',     'path' => 'contact.php'],
];
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="<?= e(SITE_NAME) ?>, home">
            <?= brand_logo('dark') ?>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
            <span class="nav-toggle__bar"></span>
            <span class="visually-hidden">Menu</span>
        </button>

        <nav id="primary-nav" class="primary-nav" aria-label="Main navigation">
            <ul class="primary-nav__list">
<?php foreach ($navItems as $item): ?>
                <li class="primary-nav__item<?= !empty($item['children']) ? ' has-dropdown' : '' ?>">
                    <a class="primary-nav__link<?= is_active($item['path']) ? ' is-active' : '' ?>"
                       href="<?= e(url($item['path'])) ?>"<?= is_active($item['path']) ? ' aria-current="page"' : '' ?>>
                        <?= e($item['label']) ?>
                    </a>
<?php if (!empty($item['children'])): ?>
                    <ul class="dropdown">
<?php foreach ($item['children'] as $child): ?>
                        <li><a class="dropdown__link<?= is_active($child['path']) ? ' is-active' : '' ?>" href="<?= e(url($child['path'])) ?>"><?= e($child['label']) ?></a></li>
<?php endforeach; ?>
                    </ul>
<?php endif; ?>
                </li>
<?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
