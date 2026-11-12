<?php
/**
 * One blog post: blog/blog-post.php?slug=...
 *
 * Visitors see Published posts only. Signed-in staff can also open a Draft
 * or Archived post, marked as a preview, to check it before publishing.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/blog.php';
require_once INCLUDES_PATH . '/newsletter-form.php';

$slug  = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$staff = staff_viewer();
$canPreview = $staff !== null && role_can($staff['Role'], 'blogs.view');

$post = preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) ? blog_post($slug, $canPreview) : null;
if ($post === null) {
    content_not_found();
}

$preview  = $post['Status'] !== 'Published';
$related  = $preview ? [] : blog_related($post);
$sections = site_sections('blog');
$signup   = $sections['post-newsletter'] ?? null;

$pageTitle       = ($post['MetaTitle'] ?? '') ?: $post['Title'];
$pageDescription = ($post['MetaDescription'] ?? '') ?: (($post['Excerpt'] ?? '') ?: SITE_TAGLINE);
$pageImage       = $post['FeaturedImage'] ?? '';
$noindex         = $preview;

$return = 'blog/blog-post.php?slug=' . $post['Slug'];

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

<?php if ($preview): ?>
    <p class="preview-banner" role="status"><strong>Preview:</strong> this post is <?= e(strtolower($post['Status'])) ?>, so only signed-in staff can see it.</p>
<?php endif; ?>

    <article class="blog-post">
        <header class="page-hero blog-post__header" aria-labelledby="page-title">
            <?= edit_block('blog-post', 'post', [['admin', 'Edit this post', 'admin/blog-edit.php?id=' . (int) $post['BlogID'], 'blogs.edit']]) ?>
            <?= botanical('botanical page-hero__botanical') ?>
            <div class="container">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                        <li><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
<?php if ($post['CategoryName']): ?>
                        <li><a href="<?= e(url('blog.php?category=' . rawurlencode($post['CategorySlug']))) ?>"><?= e($post['CategoryName']) ?></a></li>
<?php endif; ?>
                        <li aria-current="page"><?= e($post['Title']) ?></li>
                    </ol>
                </nav>
                <h1 id="page-title"><?= e($post['Title']) ?></h1>
                <p class="blog-post__meta">
                    <?= $post['PublishedDate'] ? blog_date($post['PublishedDate']) . ' &middot; ' : '' ?><?= blog_reading_minutes($post['Content']) ?> min read<?= trim((string) $post['AuthorName']) !== '' ? ' &middot; By ' . e($post['AuthorName']) : '' ?>
                </p>
            </div>
        </header>

        <div class="section blog-post__body">
            <div class="container content-narrow">
<?php if (trim((string) $post['FeaturedImage']) !== ''): ?>
                <figure class="blog-post__image">
                    <?= content_frame('art-frame', $post['FeaturedImage'], $post['FeaturedImageAlt'] ?? '', '', 'full') ?>
                </figure>
<?php endif; ?>
<?php if (trim((string) $post['Excerpt']) !== ''): ?>
                <p class="lead blog-post__lead"><?= e($post['Excerpt']) ?></p>
<?php endif; ?>
                <div class="content-document blog-post__content">
                    <?= content_document($post['Content']) ?>
                </div>
                <p class="blog-post__back"><a class="text-link" href="<?= e(url('blog.php')) ?>"><?= icon('arrow', 'icon icon--inline icon--flip') ?> All articles</a></p>
            </div>
        </div>
    </article>

<?php if ($signup && !$preview): ?>
    <section id="newsletter" class="section newsletter"<?= content_labelled($signup, 'newsletter-title') ?>>
        <?= edit_block('blog-post', 'newsletter') ?>
        <div class="container newsletter__inner">
            <div class="newsletter__text">
                <?= content_eyebrow($signup) ?>
                <?= content_heading($signup, 'newsletter-title') ?>
                <?= content_paragraphs($signup['Introduction']) ?>
            </div>
            <?= newsletter_form('newsletter', 'blog', $return, site_sections('home')['newsletter']['Body'] ?? null) ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($related): ?>
    <section class="section section--peach" aria-labelledby="related-title">
        <div class="container">
            <header class="section-header"><h2 id="related-title">More to read</h2></header>
            <ul class="card-grid card-grid--3 blog-grid">
<?php foreach ($related as $index => $item): ?>
                <?= blog_card($item, $index) ?>
<?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
