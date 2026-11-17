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
 *   ['section', label, PageKey, SectionKey, at]  a Page Sections record
 *   ['admin',   label, path, permission]          a fixed admin screen (path may end #row-Field)
 *   ['service', label, slug, field]              one service's edit screen
 *   ['legal',   label, slug, field]              one legal page's edit screen
 *
 * "at" / "field" opens the screen scrolled to that field (#row-Field), or
 * to the repeating items ("items"). Every field on an admin form has an
 * anchor id="row-Field" (see includes/cms.php).
 *
 * Visitors never see any of this: edit_block() returns nothing unless the
 * person viewing is signed in, and only shows screens their role can open.
 */

declare(strict_types=1);

function page_map(): array
{
    // Screens that list records. Item-level pages are reached from these.
    $services = ['admin', 'Services', 'admin/services.php', 'services.view'];
    $projects = ['admin', 'Portfolio Projects', 'admin/portfolio-projects.php', 'portfolio.view'];

    // One field on a single-record screen, opened in edit mode at that field.
    $home     = static fn(string $label, string $field): array => ['admin', $label, 'admin/homepage-content.php?edit=1#row-' . $field, 'homepage.edit'];
    $about    = static fn(string $label, string $field): array => ['admin', $label, 'admin/about-content.php?edit=1#row-' . $field, 'about.edit'];
    $setting  = static fn(string $label, string $field): array => ['admin', $label, 'admin/settings.php?edit=1#row-' . $field, 'settings.edit'];

    // A Page Sections record; $at is a field ('Image') or 'items' for its repeating items.
    $section  = static fn(string $page, string $key, string $label = 'Section wording', string $at = ''): array => ['section', $label, $page, $key, $at];

    $map = [
        'home' => ['title' => 'Home', 'path' => 'index.php', 'blocks' => [
            'hero'         => ['Hero: heading, wording, buttons and main image', [$home('Hero wording', 'HeroEyebrow'), $home('Hero image', 'HeroImage'), $home('Hero buttons', 'CTA1Text'), $setting('Tagline', 'Tagline')]],
            'feature-cta'  => ['Featured box', [$home('Featured box wording and link', 'FeaturedCTATitle'), $section('home', 'feature-cta', 'Button text', 'ButtonText')]],
            'introduction' => ['Introduction to Vanessa', [$section('home', 'introduction', 'Wording'), $section('home', 'introduction', 'Three foundations', 'items'), $home('Introduction image', 'IntroductionImage')]],
            'quote'        => ['Quote', [$home('Quote', 'FeaturedQuote')]],
            'services'     => ['Services overview', [$section('home', 'services', 'Heading and button'), ['admin', 'Service cards', 'admin/services.php', 'services.view']]],
            'portfolio'    => ['Portfolio highlights', [$section('home', 'portfolio', 'Heading and button'), $section('home', 'portfolio', 'Tiles and tile images', 'items')]],
            'testimonials' => ['What clients say', [$section('home', 'testimonials', 'Heading'), ['admin', 'Testimonials', 'admin/testimonials.php', 'testimonials.view']]],
            'blog'         => ['Latest blog posts', [$section('home', 'blog', 'Heading and button'), ['admin', 'Blog posts', 'admin/blogs.php', 'blogs.view'], $section('home', 'blog-empty', 'Notice while there are no posts')]],
            'newsletter'   => ['Newsletter sign-up', [$section('home', 'newsletter')]],
            'contact-cta'  => ['Contact call to action', [$section('home', 'contact-cta', 'Wording and buttons'), $section('home', 'contact-cta', 'Client types', 'items')]],
        ]],
        'about' => ['title' => 'About', 'path' => 'about.php', 'blocks' => [
            'page-header'    => ['Page header', [$section('about', 'page-header', 'Heading'), $section('about', 'page-header', 'Header image', 'Image'), $setting('Tagline', 'Tagline')]],
            'story'          => ["Vanessa's story", [$section('about', 'story', 'Heading'), $about('Story', 'Biography'), $about('Portrait', 'PortraitImage')]],
            'journey'        => ['Journey steps', [$section('about', 'journey', 'Heading'), $section('about', 'journey', 'Steps', 'items')]],
            'philosophy'     => ['Philosophy', [$section('about', 'philosophy', 'Wording'), $section('about', 'philosophy', 'List', 'items'), $section('about', 'quote', 'Quote', 'items')]],
            'values'         => ['Values', [$section('about', 'values', 'Heading'), $about('Introduction', 'CoreValues'), ['admin', 'Values', 'admin/about-values.php', 'about_items.view']]],
            'qualifications' => ['Qualifications', [$section('about', 'qualifications', 'Heading'), $about('Introduction', 'Qualifications'), ['admin', 'Qualifications', 'admin/qualifications.php', 'about_items.view'], $section('about', 'qualifications-notice', 'Notice while empty')]],
            'speaking'       => ['Speaking', [$section('about', 'speaking', 'Wording and buttons'), $section('about', 'speaking', 'Speaking image', 'Image'), $about('Speaking topics', 'SpeakingTopics'), ['admin', 'Engagements', 'admin/speaking-engagements.php', 'speaking.view'], $section('about', 'speaking-notice', 'Notice while empty')]],
            'closing'        => ['Closing call to action', [$section('about', 'closing')]],
        ]],
        'services' => ['title' => 'Services', 'path' => 'services.php', 'blocks' => [
            'page-header' => ['Page header', [$section('services', 'page-header', 'Heading'), $section('services', 'page-header', 'Header image', 'Image'), $section('services', 'seo', 'Search engine details')]],
            'service'     => ['Each service', [$services]],
            'process'     => ['How it works', [$section('services', 'process', 'Heading'), $section('services', 'process', 'Steps', 'items')]],
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
            'page-header' => ['Page header', [['service', 'Name and introduction', $slug, 'ServiceName']]],
            'main'        => ['Description, image and inclusions', [['service', 'Description', $slug, 'FullDescription'], ['service', 'Image', $slug, 'FeaturedImage'], ['service', "What's included", $slug, 'IncludedItems']]],
            'approach'    => ['How it works', [$section('service-' . $slug, 'approach', 'Heading'), $section('service-' . $slug, 'approach', 'Steps', 'items')]],
        ];
        if ($slug === 'biophilic-design') {
            $blocks['specialised'] = ['Specialised services', [$section('service-' . $slug, 'specialised', 'Heading'), $section('service-' . $slug, 'specialised', 'Items', 'items')]];
        }
        if ($slug === 'speaking-engagements') {
            $blocks['engagements'] = ['Engagements', [['admin', 'Engagements', 'admin/speaking-engagements.php', 'speaking.view']]];
        }
        $blocks['closing'] = ['Closing call to action', [$section('service-' . $slug, 'closing')]];
        $map['service-' . $slug] = ['title' => 'Service: ' . $name, 'path' => 'services/' . $slug . '.php', 'blocks' => $blocks];
    }

    $map['portfolio'] = ['title' => 'Portfolio', 'path' => 'portfolio.php', 'blocks' => [
        'page-header' => ['Page header', [$section('portfolio', 'page-header', 'Heading'), $section('portfolio', 'page-header', 'Header image', 'Image'), $section('portfolio', 'seo', 'Search engine details')]],
        'categories'  => ['Categories', [$section('portfolio', 'categories', 'Heading'), $section('portfolio', 'categories', 'Tiles and tile images', 'items')]],
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
            'page-header' => ['Page header', [$section($key, 'page-header', 'Heading'), $section($key, 'page-header', 'Header image', 'Image'), $section($key, 'seo', 'Search engine details')]],
            'intro'       => ['Introduction', [$section($key, 'intro')]],
            'list'        => [$key === 'portfolio-collaborators' ? 'Collaborators' : 'Projects', [$list, $section($key, 'empty', 'Notice while empty')]],
            'closing'     => ['Closing call to action', [$section($key, 'closing')]],
        ]];
    }

    $map['blog'] = ['title' => 'Blog', 'path' => 'blog.php', 'blocks' => [
        'page-header' => ['Page header', [$section('blog', 'page-header', 'Heading'), $section('blog', 'page-header', 'Header image', 'Image'), $section('blog', 'seo', 'Search engine details')]],
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

    $map['consultation'] = ['title' => 'Book a consultation', 'path' => 'book-consultation.php', 'blocks' => [
        'page-header' => ['Page header', [$section('consultation', 'page-header', 'Heading'), $section('consultation', 'page-header', 'Header image', 'Image'), $section('consultation', 'seo', 'Search engine details')]],
        'form'        => ['Booking form wording', [$section('consultation', 'form', 'Heading and introduction')]],
        'next'        => ['What happens next', [$section('consultation', 'next', 'Heading'), $section('consultation', 'next', 'Steps', 'items')]],
    ]];

    $map['contact'] = ['title' => 'Contact', 'path' => 'contact.php', 'blocks' => [
        'page-header' => ['Page header', [$section('contact', 'page-header', 'Heading'), $section('contact', 'page-header', 'Header image', 'Image'), $section('contact', 'seo', 'Search engine details')]],
        'get-in-touch' => ['Contact details, location and enquiry form', [
            $section('contact', 'details', 'Contact details wording'),
            $setting('Phone and email', 'Phone'),
            $setting('Social links', 'Facebook'),
            $section('contact', 'location', 'Location wording'),
            $setting('Address', 'AddressLine1'),
            $setting('Where you work', 'ServiceAreas'),
            $section('contact', 'form', 'Form wording'),
            $setting('Where messages go', 'EnquiryEmail'),
        ]],
        'faq'         => ['Frequently asked questions', [$section('contact', 'faq', 'Heading'), $section('contact', 'faq', 'Questions', 'items')]],
    ]];

    foreach (['privacy-policy' => 'Privacy Policy', 'terms-and-conditions' => 'Terms and Conditions', 'copyright' => 'Copyright Notice'] as $slug => $title) {
        $map['legal-' . $slug] = ['title' => $title, 'path' => $slug . '.php', 'blocks' => [
            'content' => ['Title and wording', [['legal', 'Page wording', $slug, 'Content']]],
        ]];
    }

    $map['global'] = ['title' => 'Every page: footer', 'path' => null, 'blocks' => [
        'footer' => ['Footer', [$section('global', 'footer', 'Footer wording and button'), $setting('Copyright line', 'FooterCopyright')]],
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
    if ($path === null) {
        return null;
    }

    // Open the screen at the field: "items" is the repeating-items list, anything else a field.
    $at = $type === 'section' ? (string) ($target[4] ?? '') : (in_array($type, ['service', 'legal'], true) ? (string) ($target[3] ?? '') : '');
    if ($at !== '') {
        $path .= $at === 'items' ? '#items' : '#row-' . $at;
    }

    return ['label' => $label, 'url' => url($path), 'permission' => $permission];
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

// ---------------------------------------------------------------------------
// Click-to-edit: every heading, paragraph, button and picture on the website
// carries data-edit="admin address#row-Field" for signed-in staff. Hovering
// it shows "Edit: Hero subtitle", which opens that exact field. Visitors get
// nothing. All of these return '' unless the viewer may edit that thing.
// ---------------------------------------------------------------------------

/** The attributes for one editable thing. $permission is the right needed to open $path. */
function edit_attr(string $path, string $permission, string $label): string
{
    $viewer = staff_viewer();
    if ($viewer === null || !role_can($viewer['Role'], $permission)) {
        return '';
    }
    return ' data-edit="' . e(url($path)) . '" data-edit-label="' . e($label) . '"';
}

/** A field's label from the admin forms, such as "Hero subtitle". */
function edit_label(string $module, string $field, string $fallback = ''): string
{
    if (staff_viewer() === null) {
        return '';
    }
    require_once INCLUDES_PATH . '/cms-modules.php';
    static $labels = [];
    $labels[$module] ??= array_map(static fn(array $f): string => (string) ($f['label'] ?? ''), cms_modules()[$module]['fields'] ?? []);
    return $labels[$module][$field] ?? ($fallback ?: $field);
}

/** A field of a Page Sections record (the section row from site_sections()). */
function ed_section(?array $section, string $field): string
{
    if ($section === null || empty($section['SectionID']) || staff_viewer() === null) {
        return '';
    }
    $where = trim(cms_safe_label((string) $section['SectionKey']));
    $label = $field === 'Image' ? 'Image' : edit_label('page_sections', $field);
    return edit_attr('admin/section-edit.php?id=' . (int) $section['SectionID'] . '#row-' . $field, 'page_sections.edit', $label . ' (' . strtolower($where) . ')');
}

/** A field of one repeating item in a section (a step, a tile, a question). */
function ed_item(?array $item, string $field = 'Title'): string
{
    if ($item === null || empty($item['ItemID']) || staff_viewer() === null) {
        return '';
    }
    $name  = trim((string) ($item['Title'] ?? '')) ?: 'item';
    $label = ['Title' => 'Title', 'Text' => 'Text', 'Image' => 'Image', 'Link' => 'Link', 'Icon' => 'Icon'][$field] ?? $field;
    return edit_attr('admin/item-edit.php?id=' . (int) $item['ItemID'] . '#row-' . $field, 'page_sections.edit', $label . ': ' . mb_strimwidth($name, 0, 40, '…'));
}

/** A field of Homepage Content, About Content or Website Settings. */
function ed_home(string $field): string
{
    return staff_viewer() === null ? '' : edit_attr('admin/homepage-content.php?edit=1#row-' . $field, 'homepage.edit', edit_label('homepage', $field));
}

function ed_about(string $field): string
{
    return staff_viewer() === null ? '' : edit_attr('admin/about-content.php?edit=1#row-' . $field, 'about.edit', edit_label('about', $field));
}

function ed_setting(string $field): string
{
    return staff_viewer() === null ? '' : edit_attr('admin/settings.php?edit=1#row-' . $field, 'settings.edit', edit_label('settings', $field) . ' (Website Settings)');
}

/**
 * A field of any other record: $module is its cms-modules key, $page its
 * edit screen (for example 'admin/service-edit.php').
 */
function ed_record(string $module, string $page, ?int $id, string $field, string $permission): string
{
    if ($id === null || $id <= 0 || staff_viewer() === null) {
        return '';
    }
    return edit_attr($page . '?id=' . $id . '#row-' . $field, $permission, edit_label($module, $field));
}

function ed_service(?array $service, string $field): string
{
    return ed_record('services', 'admin/service-edit.php', isset($service['ServiceID']) ? (int) $service['ServiceID'] : null, $field, 'services.edit');
}

// ---------------------------------------------------------------------------
// Hidden blocks: where a section exists but isn't shown (Draft or Archived),
// or a block is empty, staff see a dashed placeholder with a link to fix it.
// Visitors see nothing.
// ---------------------------------------------------------------------------

/**
 * Placeholders for this page's sections that visitors can't see. $published
 * is what site_sections() returned; $only limits it to some section keys.
 */
function hidden_blocks(string $page, array $published, array $only = []): string
{
    $viewer = staff_viewer();
    if ($viewer === null || !role_can($viewer['Role'], 'page_sections.edit')) {
        return '';
    }
    try {
        $rows = db_all("SELECT SectionID, SectionKey, Status FROM PageSections WHERE PageKey = ? AND Status <> 'Published' AND SectionKey <> 'seo' ORDER BY DisplayOrder", [$page]);
    } catch (Throwable $error) {
        return '';
    }
    $html = '';
    foreach ($rows as $row) {
        if (isset($published[$row['SectionKey']]) || ($only !== [] && !in_array($row['SectionKey'], $only, true))) {
            continue;
        }
        $label = page_map()[$page]['blocks'][$row['SectionKey']][0] ?? cms_safe_label((string) $row['SectionKey']);
        $html .= hidden_notice($label, $row['Status'] === 'Draft' ? 'a draft, not published yet' : 'archived', url('admin/section-edit.php?id=' . (int) $row['SectionID'] . '#row-Status'), 'Edit and publish');
    }
    return $html;
}

function hidden_block(string $page, string $key, array $published): string
{
    return hidden_blocks($page, $published, [$key]);
}

/** A placeholder for a block that is hidden because a field is empty. */
function hidden_field_block(string $label, string $reason, string $path, string $permission): string
{
    $viewer = staff_viewer();
    if ($viewer === null || !role_can($viewer['Role'], $permission)) {
        return '';
    }
    return hidden_notice($label, $reason, url($path), 'Add it');
}

function hidden_notice(string $label, string $reason, string $href, string $action): string
{
    return '<div class="hidden-block" role="note"><span><strong>Hidden from visitors:</strong> ' . e($label) . ' (' . e($reason) . ')</span>'
         . '<a class="hidden-block__link" href="' . e($href) . '">' . e($action) . '</a></div>';
}
