<?php
/**
 * Blog: categories, posts, search, and the publishing rules shared by the
 * public pages and the admin screens.
 *
 * Visitors only ever see Published posts whose publish date has arrived: a
 * post Published with a future date is Scheduled, and appears by itself at
 * that time. Signed-in staff can preview a Draft, Scheduled or Archived
 * post on the website before it goes live.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/content.php';

const BLOG_PAGE_SIZE = 9;

/** SQL condition: visitors can see the post (published, and its time has come). */
function blog_live_sql(string $alias = 'p'): string
{
    return "$alias.Status = 'Published' AND ($alias.PublishedDate IS NULL OR $alias.PublishedDate <= NOW())";
}

/** Published with a publish date still to come. */
function blog_is_scheduled(array $post): bool
{
    return ($post['Status'] ?? '') === 'Published' && !empty($post['PublishedDate']) && strtotime((string) $post['PublishedDate']) > time();
}

/** Visitors can see it now. */
function blog_is_live(array $post): bool
{
    return ($post['Status'] ?? '') === 'Published' && !blog_is_scheduled($post);
}

/** Status for staff screens: Draft, Scheduled, Published or Archived. */
function blog_status_label(array $post): string
{
    return blog_is_scheduled($post) ? 'Scheduled' : (string) $post['Status'];
}

function blog_post_url(string $slug): string
{
    return url('blog/blog-post.php?slug=' . rawurlencode($slug));
}

/** Rough reading time, at about 200 words a minute. */
function blog_reading_minutes(?string $content): int
{
    return max(1, (int) round(str_word_count(strip_tags((string) $content)) / 200));
}

/** Categories with their number of published posts, for the blog page filter. */
function blog_categories(): array
{
    return content_fetch('blog_categories', static fn() => db_all(
        "SELECT c.CategoryID, c.CategoryName, c.Slug, c.Description,
                COUNT(p.BlogID) AS PostCount
         FROM BlogCategories c
         LEFT JOIN BlogPosts p ON p.CategoryID = c.CategoryID AND " . blog_live_sql() . "
         GROUP BY c.CategoryID ORDER BY c.CategoryName"
    ));
}

/** The words of a search, cleaned: at most six, each at least two letters. */
function blog_search_words(string $query): array
{
    $words = preg_split('/\s+/u', mb_strtolower(trim($query))) ?: [];
    $words = array_filter($words, static fn(string $w): bool => mb_strlen($w) >= 2);
    return array_slice(array_values(array_unique($words)), 0, 6);
}

/**
 * Published posts, newest first, optionally in one category and matching
 * every word of a search. Returns [posts, total].
 *
 * @return array{0: array<int, array>, 1: int}
 */
function blog_find(?int $categoryId, string $query, int $limit, int $offset, ?int $excludeId = null): array
{
    $where  = [blog_live_sql()];
    $params = [];
    if ($categoryId !== null) {
        $where[]  = 'p.CategoryID = ?';
        $params[] = $categoryId;
    }
    if ($excludeId !== null) {
        $where[]  = 'p.BlogID <> ?';
        $params[] = $excludeId;
    }
    foreach (blog_search_words($query) as $word) {
        $like     = '%' . addcslashes($word, '%_\\') . '%';
        $where[]  = '(p.Title LIKE ? OR p.Excerpt LIKE ? OR p.Content LIKE ? OR c.CategoryName LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    $sqlWhere = implode(' AND ', $where);
    $from     = "FROM BlogPosts p LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID WHERE $sqlWhere";

    $result = content_fetch('blog_find', static fn() => [
        db_all(
            "SELECT p.BlogID, p.Title, p.Slug, p.Excerpt, p.Content, p.FeaturedImage, p.FeaturedImageAlt, p.PublishedDate, p.IsFeatured,
                    c.CategoryName, c.Slug AS CategorySlug
             $from ORDER BY p.PublishedDate DESC, p.BlogID DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        ),
        (int) db_value("SELECT COUNT(*) $from", $params),
    ]);

    return [$result[0] ?? [], $result[1] ?? 0];
}

/** The newest featured post, for the top of the blog page. */
function blog_featured(): ?array
{
    $post = content_fetch('blog_featured', static fn() => db_row(
        "SELECT p.BlogID, p.Title, p.Slug, p.Excerpt, p.Content, p.FeaturedImage, p.FeaturedImageAlt, p.PublishedDate,
                c.CategoryName, c.Slug AS CategorySlug
         FROM BlogPosts p LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID
         WHERE " . blog_live_sql() . " AND p.IsFeatured = 1
         ORDER BY p.PublishedDate DESC, p.BlogID DESC LIMIT 1"
    ) ?? []);
    return $post ?: null;
}

/** One post by its web address. Drafts and archived posts only when $preview is true. */
function blog_post(string $slug, bool $preview): ?array
{
    $post = content_fetch('blog_post', static fn() => db_row(
        "SELECT p.*, c.CategoryName, c.Slug AS CategorySlug, CONCAT(u.Name, ' ', u.Surname) AS AuthorName
         FROM BlogPosts p
         LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID
         LEFT JOIN AdminUsers u ON u.AdminUserID = p.AuthorID
         WHERE p.Slug = ?" . ($preview ? '' : ' AND ' . blog_live_sql()),
        [$slug]
    ) ?? []);
    return $post ?: null;
}

/** Up to three other published posts: same category first, then the newest. */
function blog_related(array $post): array
{
    return content_fetch('blog_related', static fn() => db_all(
        "SELECT p.BlogID, p.Title, p.Slug, p.Excerpt, p.FeaturedImage, p.FeaturedImageAlt, p.PublishedDate, c.CategoryName
         FROM BlogPosts p LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID
         WHERE " . blog_live_sql() . " AND p.BlogID <> ?
         ORDER BY (p.CategoryID <=> ?) DESC, p.PublishedDate DESC LIMIT 3",
        [(int) $post['BlogID'], $post['CategoryID']]
    ));
}

/** Every post visitors can see, for sitemap.xml. */
function blog_sitemap_posts(): array
{
    try {
        return db_all('SELECT p.Slug, p.PublishedDate, p.UpdatedDate FROM BlogPosts p WHERE ' . blog_live_sql() . ' ORDER BY p.PublishedDate DESC');
    } catch (Throwable $error) {
        return [];
    }
}

/** The newest published posts, for the home page. */
function blog_latest(int $count): array
{
    return blog_find(null, '', $count, 0)[0];
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

function blog_date(?string $date): string
{
    return $date ? '<time datetime="' . e(date('Y-m-d', strtotime($date))) . '">' . e(date('j F Y', strtotime($date))) . '</time>' : '';
}

/** A post as a card for the blog page, home page and related posts. */
function blog_card(array $post, int $index = 0, string $headingTag = 'h3'): string
{
    $tones = ['green', 'gold', 'peach'];
    $meta  = array_filter([e($post['CategoryName'] ?? ''), blog_date($post['PublishedDate'] ?? null)]);
    $id    = isset($post['BlogID']) ? (int) $post['BlogID'] : null;
    $ed    = static fn(string $field): string => ed_record('blog_posts', 'admin/blog-edit.php', $id, $field, 'blogs.edit');

    return '<li class="card card--post blog-card">'
         . content_frame('art-frame blog-card__image art-frame--' . $tones[$index % 3], $post['FeaturedImage'] ?? '', $post['FeaturedImageAlt'] ?? '', icon('pen', 'icon art-frame__icon'), 'web', $ed('FeaturedImage'))
         . '<div class="blog-card__body">'
         . ($meta ? '<p class="card__meta">' . implode(' &middot; ', $meta) . '</p>' : '')
         . '<' . $headingTag . ' class="card__title"' . $ed('Title') . '><a class="card__link" href="' . e(blog_post_url((string) $post['Slug'])) . '">' . e($post['Title']) . '</a></' . $headingTag . '>'
         . (trim((string) ($post['Excerpt'] ?? '')) !== '' ? '<p' . $ed('Excerpt') . '>' . e($post['Excerpt']) . '</p>' : '')
         . '<span class="card__more" aria-hidden="true">Read more ' . icon('arrow', 'icon icon--inline') . '</span>'
         . '</div></li>';
}

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------

/** The post module with the current categories as choices. */
function blog_post_module(): array
{
    $module = cms_module('blog_posts');
    $labels = [];
    foreach (db_all('SELECT CategoryID, CategoryName FROM BlogCategories ORDER BY CategoryName') as $row) {
        $labels[(string) $row['CategoryID']] = $row['CategoryName'];
    }
    $module['fields']['CategoryID']['options']       = array_map('strval', array_keys($labels));
    $module['fields']['CategoryID']['option_labels'] = $labels;
    return $module;
}

/**
 * "Share this article" links. Plain links to each network's own share page:
 * no scripts, buttons or trackers from those networks are loaded. "Copy
 * link" needs JavaScript and stays hidden without it.
 */
function blog_share_links(array $post): string
{
    if (mail_origin() === '') {
        return '';
    }
    $link  = absolute_url('blog/blog-post.php?slug=' . $post['Slug']);
    $title = (string) $post['Title'];
    $image = trim((string) ($post['FeaturedImage'] ?? ''));

    $links = [
        'WhatsApp' => 'https://wa.me/?text=' . rawurlencode($title . ' ' . $link),
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($link),
        'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($link),
    ];
    if ($image !== '' && link_is_safe($image)) {
        $links['Pinterest'] = 'https://www.pinterest.com/pin/create/button/?url=' . rawurlencode($link)
            . '&media=' . rawurlencode(mail_origin() . image_url($image, 'web')) . '&description=' . rawurlencode($title);
    }

    $html = '<aside class="share" aria-labelledby="share-title"><h2 class="share__title" id="share-title">Share this article</h2><ul class="share__list">';
    foreach ($links as $network => $href) {
        $html .= '<li><a class="share__link" href="' . e($href) . '" target="_blank" rel="noopener noreferrer">' . $network
               . '<span class="visually-hidden"> (opens in a new tab)</span></a></li>';
    }
    $html .= '<li><a class="share__link" href="mailto:?subject=' . e(rawurlencode($title)) . '&amp;body=' . e(rawurlencode($title . "\n\n" . $link)) . '">Email</a></li>';
    $html .= '<li><button class="share__link" type="button" data-copy-link="' . e($link) . '" hidden>Copy link</button></li>';
    return $html . '</ul><p class="share__status visually-hidden" role="status" data-copy-status></p></aside>';
}

/**
 * "About the author" under an article. When the designer wrote it (the
 * author's name matches Website Settings → Designer's name): her portrait,
 * name, job title, short bio and a link to the About page, which tells
 * readers, search engines and AI assistants who is behind the advice.
 * Anyone else: just their name.
 */
function blog_author_box(array $post): string
{
    $author = trim((string) ($post['AuthorName'] ?? ''));
    if ($author === '') {
        return '';
    }
    require_once INCLUDES_PATH . '/seo.php';
    $search = site_search_settings();
    $owner  = trim((string) ($search['OwnerName'] ?? ''));
    if ($owner === '' || strcasecmp($author, $owner) !== 0) {
        return '<aside class="author-box author-box--simple" aria-label="About the author"><p>Written by <strong>' . e($author) . '</strong></p></aside>';
    }
    $about    = site_about();
    $portrait = trim((string) ($about['PortraitImage'] ?? ''));
    $bio      = seo_short_bio();
    $title    = trim((string) ($search['OwnerTitle'] ?? ''));
    $first    = explode(' ', $owner)[0];
    return '<aside class="author-box" aria-labelledby="author-title">'
         . ($portrait !== '' && link_is_safe($portrait) ? '<img class="author-box__photo" src="' . e(image_url($portrait, 'thumb')) . '" alt="' . e($about['PortraitImageAlt'] ?? $owner) . '" width="96" height="96" loading="lazy"' . ed_about('PortraitImage') . '>' : '')
         . '<div class="author-box__text"><p class="author-box__label">About the author</p>'
         . '<h2 class="author-box__name" id="author-title"' . ed_setting('OwnerName') . '>' . e($owner) . '</h2>'
         . ($title !== '' ? '<p class="author-box__title"' . ed_setting('OwnerTitle') . '>' . e($title) . '</p>' : '')
         . ($bio !== '' ? '<p' . ed_about('ShortBio') . '>' . e($bio) . '</p>' : '')
         . '<a class="text-link" href="' . e(url('about.php')) . '">More about ' . e($first) . '</a></div></aside>';
}

/**
 * An Editor asks for a post to be published: every active person who can
 * publish is emailed (after the page loads). Returns false when nobody can be.
 */
function blog_request_publishing(int $id): bool
{
    require_once INCLUDES_PATH . '/mail.php';
    $post   = db_row('SELECT Title, Slug FROM BlogPosts WHERE BlogID = ?', [$id]);
    $editor = current_admin();
    $sent   = 0;
    foreach (db_all("SELECT Email, Name, Surname, Role FROM AdminUsers WHERE Status = 'Active' AND AdminUserID <> ?", [(int) $editor['AdminUserID']]) as $person) {
        if (!role_can((string) $person['Role'], 'blogs.publish')) {
            continue;
        }
        mail_send_later((string) $person['Email'], 'blog-review', [
            'title'      => (string) $post['Title'],
            'editor'     => trim($editor['Name'] . ' ' . $editor['Surname']),
            'adminUrl'   => absolute_url('admin/blog-edit.php?id=' . $id),
            'previewUrl' => absolute_url('blog/blog-post.php?slug=' . $post['Slug']),
        ], ['to_name' => trim($person['Name'] . ' ' . $person['Surname'])]);
        $sent++;
    }
    activity_log('request_publish', 'BlogPosts', $id, $sent . ' Admin' . ($sent === 1 ? '' : 's') . ' emailed');
    return $sent > 0;
}

/** What to tell the person after saving a post ($before is the post before an edit). */
function blog_save_message(int $id, ?array $before = null): string
{
    $post = db_row('SELECT Status, PublishedDate FROM BlogPosts WHERE BlogID = ?', [$id]) ?? ['Status' => 'Draft', 'PublishedDate' => null];
    if (blog_is_scheduled($post)) {
        return 'Saved. The post is scheduled: it appears on the website by itself on ' . date('j M Y \a\t H:i', strtotime((string) $post['PublishedDate'])) . '.';
    }
    if (blog_is_live($post) && ($before === null || !blog_is_live($before))) {
        return 'Post published.';
    }
    return $before === null ? 'Draft saved. It is not on the website until it is published.' : 'Your changes have been saved.';
}

/**
 * After a post is created or saved: stamp the date it was first published,
 * and log publishing changes as their own actions.
 */
function blog_after_save(int $id, string $before, string $after): void
{
    if ($after === 'Published') {
        db_query("UPDATE BlogPosts SET PublishedDate = NOW() WHERE BlogID = ? AND PublishedDate IS NULL", [$id]);
    }
    if ($before !== $after) {
        $action = match ($after) {
            'Published' => 'publish',
            'Archived'  => 'archive',
            default     => 'unpublish',
        };
        if (!($before === 'Draft' && $after === 'Draft')) {
            activity_log($action, 'BlogPosts', $id, $before . ' to ' . $after);
        }
    }
}
