<?php
/**
 * Blog: published posts, newest first, with category filter, search and
 * pages. The newest "featured" post is shown large at the top of page one.
 * Wording comes from Page Sections (page "Blog").
 *
 *   blog.php                       all posts
 *   blog.php?category=slug         one category
 *   blog.php?q=words               search (every word must match)
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/blog.php';
require_once INCLUDES_PATH . '/newsletter-form.php';

$sections   = site_sections('blog');
$header     = $sections['page-header'] ?? ['Heading' => 'Blog'];
$categories = blog_categories();

$query        = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$categorySlug = is_string($_GET['category'] ?? null) ? $_GET['category'] : '';
$category     = null;
foreach ($categories as $option) {
    if ($option['Slug'] === $categorySlug) {
        $category = $option;
    }
}
$page     = max(1, (int) ($_GET['page'] ?? 1));
$filtered = $category !== null || $query !== '';

// The featured post leads page one of the full list, and isn't repeated below it.
$featured = !$filtered && $page === 1 ? blog_featured() : null;
$exclude  = !$filtered ? (blog_featured()['BlogID'] ?? null) : null;

[$posts, $total] = blog_find($category ? (int) $category['CategoryID'] : null, $query, BLOG_PAGE_SIZE, ($page - 1) * BLOG_PAGE_SIZE, $exclude);
$pages    = max(1, (int) ceil($total / BLOG_PAGE_SIZE));
$anyPosts = $total > 0 || $featured !== null;

$listUrl = static fn(array $change = []): string => url('blog.php') . (($q = http_build_query(array_filter(
    array_merge(['category' => $category['Slug'] ?? null, 'q' => $query !== '' ? $query : null], $change),
    static fn($v) => $v !== null && $v !== '' && $v !== 1
))) !== '' ? '?' . $q : '');

$pageTitle       = $category ? $category['CategoryName'] . ' | ' . ($header['Heading'] ?? 'Blog') : ($header['Heading'] ?? 'Blog');
$pageDescription = ($category['Description'] ?? '') ?: (($header['Introduction'] ?? '') ?: SITE_TAGLINE);
$noindex         = $query !== '';  // search results shouldn't appear in search engines

$homeNewsletter = site_sections('home')['newsletter'] ?? null;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <?= content_page_hero($header, [], edit_block('blog', 'page-header')) ?>

    <section class="section blog-index" aria-label="Articles">
        <?= edit_block('blog', 'posts') ?>
        <div class="container">

            <div class="blog-tools">
<?php if (array_filter($categories, static fn($c) => (int) $c['PostCount'] > 0)): ?>
                <nav class="blog-categories" aria-label="Blog categories">
                    <ul class="tag-list">
                        <li><a href="<?= e(url('blog.php')) ?>"<?= $category === null ? ' aria-current="page" class="is-active"' : '' ?>>All articles</a></li>
<?php foreach ($categories as $option): if ((int) $option['PostCount'] === 0) continue; ?>
                        <li><a href="<?= e(url('blog.php?category=' . rawurlencode($option['Slug']))) ?>"<?= $category && $category['Slug'] === $option['Slug'] ? ' aria-current="page" class="is-active"' : '' ?>><?= e($option['CategoryName']) ?> <span class="blog-categories__count">(<?= (int) $option['PostCount'] ?>)</span></a></li>
<?php endforeach; ?>
                    </ul>
                </nav>
<?php endif; ?>
<?php if ($anyPosts || $filtered): ?>
                <form class="blog-search" method="get" action="<?= e(url('blog.php')) ?>" role="search">
<?php if ($category): ?>
                    <input type="hidden" name="category" value="<?= e($category['Slug']) ?>">
<?php endif; ?>
                    <label class="visually-hidden" for="blog-search">Search the blog</label>
                    <input id="blog-search" name="q" type="search" value="<?= e($query) ?>" placeholder="Search the blog" maxlength="100">
                    <button class="btn btn--primary btn--small" type="submit">Search</button>
                </form>
<?php endif; ?>
            </div>

<?php if ($filtered): ?>
            <div class="blog-results">
                <h2 class="blog-results__title"><?= $category ? e($category['CategoryName']) : 'Search results' ?></h2>
<?php if ($category && trim((string) $category['Description']) !== ''): ?>
                <p><?= e($category['Description']) ?></p>
<?php endif; ?>
                <p class="blog-results__count" role="status"><?= $total ?> article<?= $total === 1 ? '' : 's' ?><?= $query !== '' ? ' matching &ldquo;' . e($query) . '&rdquo;' : '' ?>. <a href="<?= e(url('blog.php')) ?>">Show all articles</a></p>
            </div>
<?php endif; ?>

<?php if ($featured): ?>
            <article class="blog-featured">
                <?= content_frame('art-frame blog-featured__image art-frame--peach', $featured['FeaturedImage'] ?? '', $featured['FeaturedImageAlt'] ?? '', icon('pen', 'icon art-frame__icon'), 'full') ?>
                <div class="blog-featured__body">
                    <p class="eyebrow">Featured<?= $featured['CategoryName'] ? ' &middot; ' . e($featured['CategoryName']) : '' ?></p>
                    <h2 class="blog-featured__title"><a class="card__link" href="<?= e(blog_post_url($featured['Slug'])) ?>"><?= e($featured['Title']) ?></a></h2>
                    <p class="card__meta"><?= blog_date($featured['PublishedDate']) ?> &middot; <?= blog_reading_minutes($featured['Content']) ?> min read</p>
<?php if (trim((string) $featured['Excerpt']) !== ''): ?>
                    <p><?= e($featured['Excerpt']) ?></p>
<?php endif; ?>
                    <span class="card__more" aria-hidden="true">Read the article <?= icon('arrow', 'icon icon--inline') ?></span>
                </div>
            </article>
<?php endif; ?>

<?php if ($posts): ?>
            <ul class="card-grid card-grid--3 blog-grid">
<?php foreach ($posts as $index => $post): ?>
                <?= blog_card($post, $index, 'h2') ?>
<?php endforeach; ?>
            </ul>
<?php elseif (!$anyPosts && !$filtered && ($empty = $sections['empty'] ?? null)): ?>
            <?= content_empty_state($empty) ?>
<?php elseif ($filtered && ($none = $sections['no-results'] ?? null)): ?>
            <?= content_empty_state($none) ?>
<?php endif; ?>

<?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pages of articles">
<?php if ($page > 1): ?>
                <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page - 1])) ?>" rel="prev">Newer articles</a>
<?php endif; ?>
                <span class="pagination__status">Page <?= $page ?> of <?= $pages ?></span>
<?php if ($page < $pages): ?>
                <a class="btn btn--outline btn--small" href="<?= e($listUrl(['page' => $page + 1])) ?>" rel="next">Older articles</a>
<?php endif; ?>
            </nav>
<?php endif; ?>
        </div>
    </section>

<?php if ($homeNewsletter): ?>
    <section id="newsletter" class="section newsletter"<?= content_labelled($homeNewsletter, 'newsletter-title') ?>>
        <?= edit_block('blog', 'newsletter') ?>
        <div class="container newsletter__inner">
            <div class="newsletter__text">
                <?= content_eyebrow($homeNewsletter) ?>
                <?= content_heading($homeNewsletter, 'newsletter-title') ?>
                <?= content_paragraphs($homeNewsletter['Introduction']) ?>
            </div>
            <?= newsletter_form('newsletter', 'blog', 'blog.php', $homeNewsletter['Body']) ?>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
