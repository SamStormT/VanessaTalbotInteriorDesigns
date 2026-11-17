<?php
/**
 * Marketing → Search health: everything that helps people find the website
 * on Google and in AI assistants, in one list, with a link to fix each item.
 *
 *   1. Set-up: verification codes, where you work, the designer's details
 *   2. Each page's search title and description
 *   3. Pictures in use that have no description
 *   4. Articles that could be stronger (length, picture, category, links)
 *   5. What to do outside the website (Google Business Profile and so on)
 *
 * Read only: it changes nothing. Anyone who can edit page wording can see it.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/content.php';
require_once INCLUDES_PATH . '/blog.php';
require_once INCLUDES_PATH . '/seo.php';

admin_guard('page_sections.view');

/** Plain words of formatted text, for counting. */
$words = static fn(?string $text): int => str_word_count(rich_plain_words((string) $text));
function rich_plain_words(string $text): string
{
    return (string) preg_replace(['/\[\[[^\]]*\]\]/', '/\[([^\]]+)\]\([^)]+\)/', '/[*_#>]/'], [' ', '$1', ' '], $text);
}

/** What to say about a search title and description. Returns [ok, note]. */
$judge = static function (string $title, string $description, bool $titleFallsBack): array {
    $notes = [];
    if ($title === '' && !$titleFallsBack) {
        $notes[] = 'no title';
    } elseif (mb_strlen($title) > 60) {
        $notes[] = 'title is ' . mb_strlen($title) . ' characters (aim for 60 or fewer)';
    }
    $length = mb_strlen($description);
    if ($length === 0) {
        $notes[] = 'no description: Google will choose its own words';
    } elseif ($length < 70) {
        $notes[] = 'description is short (' . $length . ' characters; aim for 70 to 155)';
    } elseif ($length > 160) {
        $notes[] = 'description is ' . $length . ' characters (aim for 155 or fewer)';
    }
    return [$notes === [], $notes === [] ? 'Good' : ucfirst(implode('; ', $notes)) . '.'];
};

// 1. Set-up.
$search   = site_search_settings();
$settings = site_settings();
$setup = [
    ['Google Search Console code', trim((string) ($search['GoogleVerification'] ?? '')) !== '', 'Lets Google confirm the website is yours, so you can see searches and submit the sitemap.', 'admin/settings.php?edit=1#row-GoogleVerification', 'settings.edit'],
    ['Bing Webmaster Tools code', trim((string) ($search['BingVerification'] ?? '')) !== '', 'The same for Bing, which also feeds ChatGPT search and Copilot.', 'admin/settings.php?edit=1#row-BingVerification', 'settings.edit'],
    ['Where you work', site_service_areas() !== [], 'Shown on the contact page and given to search engines and AI assistants.', 'admin/settings.php?edit=1#row-ServiceAreas', 'settings.edit'],
    ['Designer\'s name and job title', trim((string) ($search['OwnerName'] ?? '')) !== '' && trim((string) ($search['OwnerTitle'] ?? '')) !== '', 'Connects the business, the About page and the articles to the person behind them.', 'admin/settings.php?edit=1#row-OwnerName', 'settings.edit'],
    ['Short bio', seo_short_bio() !== '', 'Shown under articles and given to search engines and AI assistants.', 'admin/about-content.php?edit=1#row-ShortBio', 'about.edit'],
    ['Phone and email', trim((string) ($settings['Phone'] ?? '')) !== '' && trim((string) ($settings['Email'] ?? '')) !== '', 'The same details as on Google Business Profile and social media.', 'admin/settings.php?edit=1#row-Phone', 'settings.edit'],
];
$aiTraining = !empty($search['AllowAITraining']);

// 2. Search wording, page by page.
$pages = [];
$home  = site_home();
$pages[] = ['Home', ...$judge((string) ($home['MetaTitle'] ?? ''), (string) ($home['MetaDescription'] ?? ''), true), 'admin/homepage-content.php?edit=1#row-MetaDescription', 'homepage.edit'];
$about = site_about();
$pages[] = ['About', ...$judge((string) ($about['MetaTitle'] ?? ''), (string) ($about['MetaDescription'] ?? ''), true), 'admin/about-content.php?edit=1#row-MetaDescription', 'about.edit'];
foreach (['services' => 'Services', 'portfolio' => 'Portfolio', 'portfolio-biophilic-interior-design' => 'Portfolio: Biophilic Interior Design', 'portfolio-colour-psychology' => 'Portfolio: Colour & Design Psychology', 'portfolio-photography-gallery' => 'Portfolio: Photography', 'portfolio-collaborators' => 'Portfolio: Collaborators', 'blog' => 'Blog', 'consultation' => 'Book a consultation', 'contact' => 'Contact'] as $key => $label) {
    $sections = site_sections($key);
    $seo      = $sections['seo'] ?? null;
    $header   = $sections['page-header'] ?? [];
    $title    = trim((string) ($seo['Heading'] ?? '')) ?: (string) ($header['Heading'] ?? '');
    $desc     = trim((string) ($seo['Introduction'] ?? '')) ?: (string) ($header['Introduction'] ?? '');
    $sectionId = $seo['SectionID'] ?? db_value("SELECT SectionID FROM PageSections WHERE PageKey = ? AND SectionKey = 'seo'", [$key]);
    $pages[] = [$label, ...$judge($title, $desc, true), $sectionId ? 'admin/section-edit.php?id=' . (int) $sectionId . '#row-Introduction' : 'admin/pages.php', 'page_sections.edit'];
}
foreach (db_all("SELECT ServiceID, ServiceName, MetaTitle, MetaDescription, ShortDescription FROM Services WHERE Status = 'Published' ORDER BY DisplayOrder") as $service) {
    $pages[] = ['Service: ' . $service['ServiceName'], ...$judge(trim((string) $service['MetaTitle']) ?: (string) $service['ServiceName'], trim((string) $service['MetaDescription']) ?: (string) $service['ShortDescription'], true), 'admin/service-edit.php?id=' . (int) $service['ServiceID'] . '#row-MetaDescription', 'services.edit'];
}
foreach (db_all("SELECT ProjectID, Title, MetaTitle, MetaDescription FROM PortfolioProjects WHERE Status = 'Published' AND ClientPermission = 1 ORDER BY Title") as $project) {
    $pages[] = ['Project: ' . $project['Title'], ...$judge(trim((string) $project['MetaTitle']) ?: (string) $project['Title'], (string) $project['MetaDescription'], true), 'admin/project-edit.php?id=' . (int) $project['ProjectID'] . '#row-MetaDescription', 'portfolio.edit'];
}
$posts = db_all('SELECT p.BlogID, p.Title, p.MetaTitle, p.MetaDescription, p.Excerpt, p.Content, p.FeaturedImage, p.FeaturedImageAlt, p.CategoryID FROM BlogPosts p WHERE ' . blog_live_sql() . ' ORDER BY p.PublishedDate DESC');
foreach ($posts as $post) {
    $pages[] = ['Article: ' . $post['Title'], ...$judge(trim((string) $post['MetaTitle']) ?: (string) $post['Title'], trim((string) $post['MetaDescription']) ?: (string) $post['Excerpt'], true), 'admin/blog-edit.php?id=' . (int) $post['BlogID'] . '#row-MetaDescription', 'blogs.edit'];
}
$pagesToImprove = count(array_filter($pages, static fn(array $p): bool => !$p[1]));

// 3. Pictures in use without a description.
$undescribed = [];
foreach (db_all("SELECT MediaID, FileName, FilePath FROM MediaLibrary WHERE AltText IS NULL OR AltText = '' ORDER BY UploadDate DESC") as $media) {
    $usage = media_usage((string) $media['FilePath']);
    if ($usage !== []) {
        $undescribed[] = $media + ['Usage' => implode(', ', $usage)];
    }
}

// 4. Articles that could be stronger.
$articleNotes = [];
foreach ($posts as $post) {
    $notes = [];
    $count = $words($post['Content']);
    if ($count < 300) {
        $notes[] = $count . ($count === 1 ? ' word' : ' words') . ' (aim for 300 or more)';
    }
    if (trim((string) $post['FeaturedImage']) === '') {
        $notes[] = 'no featured picture';
    } elseif (trim((string) $post['FeaturedImageAlt']) === '') {
        $notes[] = 'the featured picture has no description';
    }
    if (empty($post['CategoryID'])) {
        $notes[] = 'no category';
    }
    if (!preg_match('/\]\((?!https?:|mailto:|tel:)[^)\s]+\)|\[\[button [^|\]]+\|\s*(?!https?:)\S+?\s*\]\]/', (string) $post['Content'])) {
        $notes[] = 'no link to a service or another article';
    }
    if (!preg_match('/^#{2,3}\s/m', (string) $post['Content'])) {
        $notes[] = 'no subheadings';
    }
    if ($notes !== []) {
        $articleNotes[] = [$post, ucfirst(implode('; ', $notes)) . '.'];
    }
}

$fix = static fn(string $path, string $permission, string $label = 'Fix'): string =>
    can($permission) ? '<a class="btn btn--outline btn--small" href="' . e(url($path)) . '">' . e($label) . '</a>' : '';
$badge = static fn(bool $ok): string => '<span class="seo-check__mark ' . ($ok ? 'is-good-mark' : 'is-todo-mark') . '">' . ($ok ? 'Good' : 'To improve') . '</span>';

$pageTitle = 'Search health';
$layout    = 'admin';
require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content search-health">
    <p class="admin-help">Everything that helps people find the website on Google and in AI assistants such as ChatGPT. Search engines and AI assistants only see the website once it is live at its real address.</p>

    <section class="admin-panel" aria-labelledby="setup-title">
        <h2 class="admin-panel__title" id="setup-title">Set-up</h2>
        <ul class="health-list">
<?php foreach ($setup as [$label, $ok, $why, $path, $permission]): ?>
            <li><?= $badge($ok) ?> <strong><?= e($label) ?></strong> <span class="admin-muted"><?= e($why) ?></span> <?= $ok ? '' : $fix($path, $permission) ?></li>
<?php endforeach; ?>
            <li><span class="seo-check__mark is-good-mark">Set</span> <strong>AI training</strong> <span class="admin-muted"><?= $aiTraining ? 'Allowed: AI companies may train on the website, and AI search tools may read it.' : 'Not allowed: AI search tools may read and recommend the website, but AI companies are asked not to train on it.' ?></span> <?= $fix('admin/settings.php?edit=1#row-AllowAITraining', 'settings.edit', 'Change') ?></li>
        </ul>
    </section>

    <section class="admin-panel" aria-labelledby="pages-title">
        <h2 class="admin-panel__title" id="pages-title">Search titles and descriptions <span class="admin-filter__count"><?= $pagesToImprove ?> to improve</span></h2>
        <p class="admin-help">What Google shows for each page. A page without its own wording uses its heading and introduction.</p>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th scope="col">Page</th><th scope="col">Result</th><th scope="col"><span class="visually-hidden">Fix</span></th></tr></thead>
                <tbody>
<?php foreach ($pages as [$label, $ok, $note, $path, $permission]): ?>
                    <tr><td><?= e($label) ?></td><td><?= $badge($ok) ?> <?= $ok ? '' : e($note) ?></td><td class="admin-table__actions"><?= $fix($path, $permission, $ok ? 'Edit' : 'Fix') ?></td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel" aria-labelledby="pictures-title">
        <h2 class="admin-panel__title" id="pictures-title">Pictures without a description <span class="admin-filter__count"><?= count($undescribed) ?></span></h2>
<?php if ($undescribed === []): ?>
        <p><?= $badge(true) ?> Every picture on the website is described.</p>
<?php else: ?>
        <p class="admin-help">Search engines and AI assistants read picture descriptions, and they help people who can't see the pictures.</p>
        <ul class="health-list">
<?php foreach ($undescribed as $media): ?>
            <li><?= $badge(false) ?> <strong><?= e($media['FileName']) ?></strong> <span class="admin-muted">used in <?= e($media['Usage']) ?></span> <?= $fix('admin/media-edit.php?id=' . (int) $media['MediaID'] . '#row-AltText', 'media.edit', 'Describe it') ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </section>

    <section class="admin-panel" aria-labelledby="articles-title">
        <h2 class="admin-panel__title" id="articles-title">Articles <span class="admin-filter__count"><?= count($articleNotes) ?> to improve</span></h2>
<?php if ($posts === []): ?>
        <p class="admin-help">No articles published yet. Regular articles that each answer one question clients ask are one of the best ways to be found, by Google and by AI assistants.</p>
<?php elseif ($articleNotes === []): ?>
        <p><?= $badge(true) ?> Every published article has a good length, a described picture, a category, subheadings and a link to another page.</p>
<?php else: ?>
        <ul class="health-list">
<?php foreach ($articleNotes as [$post, $note]): ?>
            <li><?= $badge(false) ?> <strong><?= e($post['Title']) ?></strong> <span class="admin-muted"><?= e($note) ?></span> <?= $fix('admin/blog-edit.php?id=' . (int) $post['BlogID'], 'blogs.edit') ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </section>

    <section class="admin-panel" aria-labelledby="outside-title">
        <h2 class="admin-panel__title" id="outside-title">Outside the website</h2>
        <p class="admin-help">These matter as much as the website itself, especially for local searches. They can't be checked from here.</p>
        <ol class="health-steps">
            <li><strong>Google Business Profile</strong> (<a href="https://business.google.com" rel="noopener" target="_blank">business.google.com<span class="visually-hidden"> (opens in a new tab)</span></a>): category "Interior designer", the areas you work in, photos, and ask happy clients for a review. The biggest single step for local searches.</li>
            <li><strong>Google Search Console</strong> (<a href="https://search.google.com/search-console" rel="noopener" target="_blank">search.google.com/search-console<span class="visually-hidden"> (opens in a new tab)</span></a>): add the website, paste its code above, then submit the sitemap: <code><?= e(mail_origin() !== '' ? absolute_url('sitemap.xml') : 'sitemap.xml') ?></code></li>
            <li><strong>Bing Webmaster Tools</strong> (<a href="https://www.bing.com/webmasters" rel="noopener" target="_blank">bing.com/webmasters<span class="visually-hidden"> (opens in a new tab)</span></a>): the same; it can import everything from Search Console.</li>
            <li><strong>The same details everywhere</strong>: business name, phone and email exactly as on this website, on Instagram, Facebook, LinkedIn, Pinterest, Houzz and South African directories.</li>
            <li><strong>Links from others</strong>: collaborators, venues where Vanessa speaks, suppliers and local press linking to the website.</li>
            <li><strong>Check the details search engines read</strong> with Google's test (<a href="https://search.google.com/test/rich-results" rel="noopener" target="_blank">Rich Results Test<span class="visually-hidden"> (opens in a new tab)</span></a>) once the website is live.</li>
        </ol>
    </section>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
