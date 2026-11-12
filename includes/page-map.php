<?php
/**
 * Where everything on the website is edited.
 *
 * page_map() lists every public page and the blocks on it, in the order they
 * appear, with the admin screens that edit each block. It drives both:
 *   - the Edit buttons signed-in staff see on the website (edit_block()), and
 *   - the admin "Pages" screen (admin/pages.php).
 *
 * A target is one of:
 *   ['section', label, PageKey, SectionKey]   a Page Sections record
 *   ['admin',   label, path, permission]      a fixed admin screen
 *   ['service', label, slug]                  one service's edit screen
 *   ['legal',   label, slug]                  one legal page's edit screen
 *
 * Visitors never see any of this: edit_block() returns nothing unless the
 * person viewing is signed in, and only shows screens their role can open.
 */

declare(strict_types=1);

function page_map(): array
{
    $home      = ['admin', 'Homepage Content', 'admin/homepage-content.php', 'homepage.view'];
    $about     = ['admin', 'About Content', 'admin/about-content.php', 'about.view'];
    $settings  = ['admin', 'Website Settings', 'admin/settings.php', 'settings.view'];
    $services  = ['admin', 'Services', 'admin/services.php', 'services.view'];
    $projects  = ['admin', 'Portfolio Projects', 'admin/portfolio-projects.php', 'portfolio.view'];
    $section   = static fn(string $page, string $key, string $label = 'Section wording'): array => ['section', $label, $page, $key];

    $map = [
        'home' => ['title' => 'Home', 'path' => 'index.php', 'blocks' => [
            'hero'         => ['Hero: heading, wording, buttons and main image', [['admin', 'Hero wording and image', 'admin/homepage-content.php', 'homepage.view'], ['admin', 'Tagline', 'admin/settings.php', 'settings.view']]],
            'feature-cta'  => ['Featured box', [['admin', 'Featured box wording and link', 'admin/homepage-content.php', 'homepage.view'], $section('home', 'feature-cta', 'Button text')]],
            'introduction' => ['Introduction to Vanessa', [$section('home', 'introduction', 'Wording and three foundations'), ['admin', 'Introduction image', 'admin/homepage-content.php', 'homepage.view']]],
            'quote'        => ['Quote', [['admin', 'Quote', 'admin/homepage-content.php', 'homepage.view']]],
            'services'     => ['Services overview', [$section('home', 'services', 'Heading and button'), ['admin', 'Service cards', 'admin/services.php', 'services.view']]],
            'portfolio'    => ['Portfolio highlights', [$section('home', 'portfolio', 'Heading, tiles and tile images')]],
            'blog'         => ['Latest blog posts', [$section('home', 'blog', 'Heading and button'), ['admin', 'Blog posts', 'admin/blogs.php', 'blogs.view'], $section('home', 'blog-empty', 'Notice while there are no posts')]],
            'newsletter'   => ['Newsletter sign-up', [$section('home', 'newsletter')]],
            'contact-cta'  => ['Contact call to action', [$section('home', 'contact-cta', 'Wording, client types and buttons')]],
        ]],
        'about' => ['title' => 'About', 'path' => 'about.php', 'blocks' => [
            'page-header'    => ['Page header', [$section('about', 'page-header', 'Heading and header image'), ['admin', 'Tagline', 'admin/settings.php', 'settings.view']]],
            'story'          => ["Vanessa's story", [$section('about', 'story', 'Heading'), ['admin', 'Story and portrait', 'admin/about-content.php', 'about.view']]],
            'journey'        => ['Journey steps', [$section('about', 'journey', 'Heading and steps')]],
            'philosophy'     => ['Philosophy', [$section('about', 'philosophy', 'Wording and list'), $section('about', 'quote', 'Quote')]],
            'values'         => ['Values', [$section('about', 'values', 'Heading'), ['admin', 'Introduction', 'admin/about-content.php', 'about.view'], ['admin', 'Values', 'admin/about-values.php', 'about_items.view']]],
            'qualifications' => ['Qualifications', [$section('about', 'qualifications', 'Heading'), ['admin', 'Introduction', 'admin/about-content.php', 'about.view'], ['admin', 'Qualifications', 'admin/qualifications.php', 'about_items.view'], $section('about', 'qualifications-notice', 'Notice while empty')]],
            'speaking'       => ['Speaking', [$section('about', 'speaking', 'Wording, buttons and image'), ['admin', 'Speaking topics', 'admin/about-content.php', 'about.view'], ['admin', 'Engagements', 'admin/speaking-engagements.php', 'speaking.view'], $section('about', 'speaking-notice', 'Notice while empty')]],
            'closing'        => ['Closing call to action', [$section('about', 'closing')]],
        ]],
        'services' => ['title' => 'Services', 'path' => 'services.php', 'blocks' => [
            'page-header' => ['Page header', [$section('services', 'page-header', 'Heading and header image'), $section('services', 'seo', 'Search engine details')]],
            'service'     => ['Each service', [$services]],
            'process'     => ['How it works', [$section('services', 'process', 'Heading and steps')]],
            'closing'     => ['Closing call to action', [$section('services', 'closing')]],
        ]],
    ];

    $servicePages = [
        'biophilic-design' => 'Biophilic Design', 'colour-psychology' => 'Colour & Design Psychology',
        'photography-services' => 'Photography', 'workshops' => 'Workshops',
        'speaking-engagements' => 'Speaking Engagements', 'consultations' => 'Consultations',
    ];
    foreach ($servicePages as $slug => $name) {
        $blocks = [
            'page-header' => ['Page header', [['service', 'Name and introduction', $slug]]],
            'main'        => ['Description, image and inclusions', [['service', 'Service details and image', $slug]]],
            'approach'    => ['How it works', [$section('service-' . $slug, 'approach', 'Heading and steps')]],
        ];
        if ($slug === 'biophilic-design') {
            $blocks['specialised'] = ['Specialised services', [$section('service-' . $slug, 'specialised', 'Heading and items')]];
        }
        if ($slug === 'speaking-engagements') {
            $blocks['engagements'] = ['Engagements', [['admin', 'Engagements', 'admin/speaking-engagements.php', 'speaking.view']]];
        }
        $blocks['closing'] = ['Closing call to action', [$section('service-' . $slug, 'closing')]];
        $map['service-' . $slug] = ['title' => 'Service: ' . $name, 'path' => 'services/' . $slug . '.php', 'blocks' => $blocks];
    }

    $map['portfolio'] = ['title' => 'Portfolio', 'path' => 'portfolio.php', 'blocks' => [
        'page-header' => ['Page header', [$section('portfolio', 'page-header', 'Heading and header image')]],
        'categories'  => ['Categories', [$section('portfolio', 'categories', 'Heading, tiles and tile images')]],
        'featured'    => ['Featured projects', [$section('portfolio', 'featured', 'Heading'), $projects]],
        'closing'     => ['Closing call to action', [$section('portfolio', 'closing')]],
    ]];

    $categories = [
        'portfolio-biophilic-interior-design' => ['Portfolio: Biophilic Interior Design', 'portfolio/biophilic-interior-design.php', $projects],
        'portfolio-colour-psychology'         => ['Portfolio: Colour & Design Psychology', 'portfolio/colour-psychology.php', $projects],
        'portfolio-photography-gallery'       => ['Portfolio: Photography', 'portfolio/photography-gallery.php', $projects],
        'portfolio-collaborators'             => ['Portfolio: Collaborators', 'portfolio/collaborators.php', ['admin', 'Collaborators', 'admin/collaborators.php', 'collaborators.view']],
    ];
    foreach ($categories as $key => [$title, $path, $list]) {
        $map[$key] = ['title' => $title, 'path' => $path, 'blocks' => [
            'page-header' => ['Page header', [$section($key, 'page-header', 'Heading and header image')]],
            'intro'       => ['Introduction', [$section($key, 'intro')]],
            'list'        => [$key === 'portfolio-collaborators' ? 'Collaborators' : 'Projects', [$list, $section($key, 'empty', 'Notice while empty')]],
            'closing'     => ['Closing call to action', [$section($key, 'closing')]],
        ]];
    }

    $map['blog'] = ['title' => 'Blog', 'path' => 'blog.php', 'blocks' => [
        'page-header' => ['Page header', [$section('blog', 'page-header', 'Heading and header image')]],
        'posts'       => ['Articles, categories and search', [
            ['admin', 'Blog posts', 'admin/blogs.php', 'blogs.view'],
            ['admin', 'Categories', 'admin/blog-categories.php', 'blogs.view'],
            $section('blog', 'empty', 'Notice while there are no posts'),
            $section('blog', 'no-results', 'Notice when a search finds nothing'),
        ]],
        'newsletter'  => ['Newsletter sign-up (same wording as the home page)', [$section('home', 'newsletter')]],
    ]];

    $map['blog-post'] = ['title' => 'Blog: single article', 'path' => null, 'blocks' => [
        'post'       => ['Article, image and summary', [['admin', 'Blog posts', 'admin/blogs.php', 'blogs.view']]],
        'newsletter' => ['Newsletter sign-up under each article', [$section('blog', 'post-newsletter')]],
    ]];

    $map['project'] = ['title' => 'Portfolio: single project', 'path' => null, 'blocks' => [
        'project' => ['Project, images and gallery', [$projects]],
    ]];

    $map['contact'] = ['title' => 'Contact', 'path' => 'contact.php', 'blocks' => [
        'page-header' => ['Page header', [$section('contact', 'page-header', 'Heading and header image')]],
        'get-in-touch' => ['Contact details, location and enquiry form', [
            $section('contact', 'details', 'Contact details wording'),
            $section('contact', 'location', 'Location wording'),
            $section('contact', 'form', 'Form wording'),
            ['admin', 'Phone, email, address, social links and where messages go', 'admin/settings.php', 'settings.view'],
        ]],
        'faq'         => ['Frequently asked questions', [$section('contact', 'faq', 'Heading and questions')]],
    ]];

    foreach (['privacy-policy' => 'Privacy Policy', 'terms-and-conditions' => 'Terms and Conditions', 'copyright' => 'Copyright Notice'] as $slug => $title) {
        $map['legal-' . $slug] = ['title' => $title, 'path' => $slug . '.php', 'blocks' => [
            'content' => ['Title and wording', [['legal', 'Page wording', $slug]]],
        ]];
    }

    $map['global'] = ['title' => 'Every page: footer', 'path' => null, 'blocks' => [
        'footer' => ['Footer', [$section('global', 'footer', 'Footer wording and button'), $settings]],
    ]];

    return $map;
}

// ---------------------------------------------------------------------------
// Resolving targets into links
// ---------------------------------------------------------------------------

/** IDs for sections, services and legal pages, looked up once per request. */
function page_map_ids(): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = ['section' => [], 'service' => [], 'legal' => []];
        foreach (db_all('SELECT SectionID, PageKey, SectionKey FROM PageSections') as $row) {
            $ids['section'][$row['PageKey'] . '/' . $row['SectionKey']] = (int) $row['SectionID'];
        }
        foreach (db_all('SELECT ServiceID, Slug FROM Services') as $row) {
            $ids['service'][$row['Slug']] = (int) $row['ServiceID'];
        }
        foreach (db_all('SELECT PageID, Slug FROM SitePages') as $row) {
            $ids['legal'][$row['Slug']] = (int) $row['PageID'];
        }
    }
    return $ids;
}

/**
 * A target as ['label' => ..., 'url' => ..., 'permission' => ...], or null when
 * the record it points at doesn't exist.
 */
function page_map_link(array $target): ?array
{
    $ids = page_map_ids();
    [$type, $label] = $target;

    [$path, $permission] = match ($type) {
        'section' => isset($ids['section'][$target[2] . '/' . $target[3]])
            ? ['admin/section-edit.php?id=' . $ids['section'][$target[2] . '/' . $target[3]], 'page_sections.edit'] : [null, null],
        'service' => isset($ids['service'][$target[2]])
            ? ['admin/service-edit.php?id=' . $ids['service'][$target[2]], 'services.edit'] : [null, null],
        'legal'   => isset($ids['legal'][$target[2]])
            ? ['admin/site-page-edit.php?id=' . $ids['legal'][$target[2]], 'site_pages.edit'] : [null, null],
        'admin'   => [$target[2], $target[3]],
        default   => [null, null],
    };

    return $path === null ? null : ['label' => $label, 'url' => url($path), 'permission' => $permission];
}

// ---------------------------------------------------------------------------
// Edit buttons on the website
// ---------------------------------------------------------------------------

/**
 * The signed-in staff member looking at the website, or null for visitors.
 * A session is only opened when the browser already has a session cookie, so
 * visitors' pages are never affected.
 */
function staff_viewer(): ?array
{
    static $checked = false, $viewer = null;
    if ($checked) {
        return $viewer;
    }
    $checked = true;

    if (empty($_COOKIE['VTIDSESSION']) || PHP_SAPI === 'cli') {
        return null;
    }
    try {
        require_once INCLUDES_PATH . '/auth.php';
        $viewer = current_admin();
    } catch (Throwable $error) {
        $viewer = null; // database unavailable: just no edit buttons
    }
    return $viewer;
}

/**
 * Edit buttons for one block of a page. $extra adds targets known only to
 * the template (for example the project being shown). Unknown block names
 * fall back to the Page Sections record with that key.
 */
function edit_block(string $page, string $block, array $extra = []): string
{
    if (staff_viewer() === null) {
        return '';
    }

    $map   = page_map();
    $entry = $map[$page]['blocks'][$block] ?? null;
    $label = $entry[0] ?? cms_safe_label($block);
    $targets = $entry[1] ?? [['section', 'Section wording', $page, $block]];

    $links = '';
    foreach (array_merge($targets, $extra) as $target) {
        $link = page_map_link($target);
        if ($link !== null && role_can(staff_viewer()['Role'], $link['permission'])) {
            $links .= '<a class="edit-here__link" href="' . e($link['url']) . '">' . icon('pen', 'icon icon--inline') . ' ' . e($link['label']) . '</a>';
        }
    }

    return $links === '' ? '' : '<div class="edit-here" role="group" aria-label="' . e('Edit: ' . $label) . '">' . $links . '</div>';
}

/** "page-header" becomes "Page header", for blocks not named in the map. */
function cms_safe_label(string $key): string
{
    return ucfirst(str_replace('-', ' ', $key));
}

/** The map entry for the page being viewed, used by the staff bar. */
function page_map_current(): ?string
{
    $path = current_path();
    if ($path === 'portfolio/project.php') {
        return 'project';
    }
    if ($path === 'blog/blog-post.php') {
        return 'blog-post';
    }
    foreach (page_map() as $key => $page) {
        if ($page['path'] === $path) {
            return $key;
        }
    }
    return null;
}
