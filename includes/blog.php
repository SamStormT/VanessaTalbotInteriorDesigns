<?php
/**
 * Blog: categories, posts, search, and the publishing rules shared by the
 * public pages and the admin screens.
 *
 * Visitors only ever see Published posts. Signed-in staff can preview a
 * Draft or Archived post on the website before it goes live.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/content.php';

const BLOG_PAGE_SIZE = 9;

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
         LEFT JOIN BlogPosts p ON p.CategoryID = c.CategoryID AND p.Status = 'Published'
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
    $where  = ["p.Status = 'Published'"];
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
         WHERE p.Status = 'Published' AND p.IsFeatured = 1
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
         WHERE p.Slug = ?" . ($preview ? '' : " AND p.Status = 'Published'"),
        [$slug]
    ) ?? []);
    return $post ?: null;
}

/** Up to three other published posts: same category first, then the newest. */
function blog_related(array $post): array
{
    return content_fetch('blog_related', static fn() => db_all(
        "SELECT p.Title, p.Slug, p.Excerpt, p.FeaturedImage, p.FeaturedImageAlt, p.PublishedDate, c.CategoryName
         FROM BlogPosts p LEFT JOIN BlogCategories c ON c.CategoryID = p.CategoryID
         WHERE p.Status = 'Published' AND p.BlogID <> ?
         ORDER BY (p.CategoryID <=> ?) DESC, p.PublishedDate DESC LIMIT 3",
        [(int) $post['BlogID'], $post['CategoryID']]
    ));
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

    return '<li class="card card--post blog-card">'
         . content_frame('art-frame blog-card__image art-frame--' . $tones[$index % 3], $post['FeaturedImage'] ?? '', $post['FeaturedImageAlt'] ?? '', icon('pen', 'icon art-frame__icon'))
         . '<div class="blog-card__body">'
         . ($meta ? '<p class="card__meta">' . implode(' &middot; ', $meta) . '</p>' : '')
         . '<' . $headingTag . ' class="card__title"><a class="card__link" href="' . e(blog_post_url((string) $post['Slug'])) . '">' . e($post['Title']) . '</a></' . $headingTag . '>'
         . (trim((string) ($post['Excerpt'] ?? '')) !== '' ? '<p>' . e($post['Excerpt']) . '</p>' : '')
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
