<?php
/**
 * Admin dashboard: counts for the modules this role can see, placeholder
 * statistics, and empty-state cards for features still to come.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/enquiries.php';

admin_guard('dashboard.view');

$counts = [
    ['label' => 'Services',             'can' => 'services.view',     'url' => 'admin/services.php',             'sql' => 'SELECT COUNT(*) FROM Services'],
    ['label' => 'Portfolio Projects',   'can' => 'portfolio.view',    'url' => 'admin/portfolio-projects.php',   'sql' => 'SELECT COUNT(*) FROM PortfolioProjects'],
    ['label' => 'Photography Products', 'can' => 'products.view',     'url' => 'admin/photography-products.php', 'sql' => 'SELECT COUNT(*) FROM PhotographyProducts'],
    ['label' => 'Speaking Engagements', 'can' => 'speaking.view',     'url' => 'admin/speaking-engagements.php', 'sql' => 'SELECT COUNT(*) FROM SpeakingEngagements'],
    ['label' => 'Testimonials',         'can' => 'testimonials.view', 'url' => 'admin/testimonials.php',         'sql' => 'SELECT COUNT(*) FROM Testimonials'],
    ['label' => 'Blog Posts',           'can' => 'blogs.view',        'url' => 'admin/blogs.php',                'sql' => 'SELECT COUNT(*) FROM BlogPosts'],
    ['label' => 'Media Files',          'can' => 'media.view',        'url' => 'admin/media-library.php',        'sql' => 'SELECT COUNT(*) FROM MediaLibrary'],
    // Counted live, so an unsubscribe shows here straight away.
    ['label' => 'Confirmed Subscribers', 'can' => 'subscribers.view', 'url' => 'admin/subscribers.php?show=confirmed', 'sql' => "SELECT COUNT(*) FROM Subscribers WHERE SubscriptionStatus = 'Subscribed' AND ConfirmedDate IS NOT NULL"],
    ['label' => 'Unsubscribed (30 days)', 'can' => 'subscribers.view', 'url' => 'admin/subscribers.php?show=unsubscribed', 'sql' => "SELECT COUNT(*) FROM Subscribers WHERE SubscriptionStatus = 'Unsubscribed' AND UnsubscribedDate >= NOW() - INTERVAL 30 DAY"],
    ['label' => 'Campaigns Sent',       'can' => 'subscribers.view',  'url' => 'admin/campaigns.php?status=Sent',  'sql' => "SELECT COUNT(*) FROM NewsletterCampaigns WHERE SentDate IS NOT NULL"],
    ['label' => 'New Messages',         'can' => 'messages.view',     'url' => 'admin/enquiries.php',            'sql' => "SELECT COUNT(*) FROM ContactMessages WHERE Status = 'New'"],
    ['label' => 'Users',                'can' => 'users.view',        'url' => 'admin/admin-users.php',          'sql' => "SELECT COUNT(*) FROM AdminUsers WHERE Status = 'Active'"],
];
$counts = array_values(array_filter($counts, static fn(array $item): bool => can($item['can'])));

$activity = can('activity.view')
    ? db_all(
        "SELECT a.Action, a.EntityType, a.EntityID, a.CreatedDate, a.AdminUserID, CONCAT(u.Name, ' ', u.Surname) AS UserName
         FROM ActivityLog a LEFT JOIN AdminUsers u ON u.AdminUserID = a.AdminUserID
         ORDER BY a.LogID DESC LIMIT 8"
      )
    : [];

// Actions taken by website visitors rather than staff (see activity_log_visitor()).
$visitorActions = ['contact_message', 'newsletter_signup', 'newsletter_resubscribe', 'newsletter_confirm', 'newsletter_unsubscribe'];

// Blog drafts waiting for someone who can publish them.
$draftPosts = can('blogs.publish')
    ? db_all("SELECT p.BlogID, p.Title, p.LastEditedDate, p.CreatedDate, CONCAT(a.Name, ' ', a.Surname) AS AuthorName
              FROM BlogPosts p LEFT JOIN AdminUsers a ON a.AdminUserID = p.AuthorID
              WHERE p.Status = 'Draft' ORDER BY COALESCE(p.LastEditedDate, p.CreatedDate) DESC LIMIT 5")
    : [];

$latestMessages = can('messages.view')
    ? db_all("SELECT MessageID, Name, Surname, EnquiryType, CreatedDate FROM ContactMessages WHERE Status = 'New' ORDER BY CreatedDate DESC LIMIT 5")
    : [];

$pageTitle = 'Dashboard';
$layout    = 'admin';
$admin     = current_admin();

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <p class="admin-welcome">Welcome back, <?= e($admin['Name']) ?>.</p>

    <h2 class="admin-section-title">At a glance</h2>
    <ul class="admin-stats">
<?php foreach ($counts as $item): ?>
        <li class="admin-stat">
            <a href="<?= e(url($item['url'])) ?>">
                <span class="admin-stat__value"><?= (int) db_value($item['sql']) ?></span>
                <span class="admin-stat__label"><?= e($item['label']) ?></span>
            </a>
        </li>
<?php endforeach; ?>
    </ul>

    <h2 class="admin-section-title">Website statistics</h2>
    <ul class="admin-stats">
        <li class="admin-stat admin-stat--placeholder"><span class="admin-stat__value">&ndash;</span><span class="admin-stat__label">Visitors this month</span></li>
        <li class="admin-stat admin-stat--placeholder"><span class="admin-stat__value">&ndash;</span><span class="admin-stat__label">Most viewed page</span></li>
        <li class="admin-stat admin-stat--placeholder"><span class="admin-stat__value">&ndash;</span><span class="admin-stat__label">Enquiries this month</span></li>
    </ul>
    <p class="admin-help">Statistics will appear here once website analytics is connected.</p>

<?php if ($activity !== []): ?>
    <h2 class="admin-section-title">Recent activity</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th scope="col">When</th><th scope="col">User</th><th scope="col">Action</th><th scope="col">Table</th><th scope="col">Record</th></tr></thead>
            <tbody>
<?php foreach ($activity as $entry): ?>
                <tr>
                    <td><?= e(cms_format_date($entry['CreatedDate'])) ?></td>
                    <td><?= e($entry['UserName'] ?? (in_array($entry['Action'], $visitorActions, true) ? 'Website visitor' : 'Former user')) ?></td>
                    <td><?= e($entry['Action']) ?></td>
                    <td><?= e($entry['EntityType'] ?? '') ?></td>
                    <td><?= e((string) ($entry['EntityID'] ?? '')) ?></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($latestMessages !== []): ?>
    <h2 class="admin-section-title">New messages</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th scope="col">Received</th><th scope="col">From</th><th scope="col">About</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
<?php foreach ($latestMessages as $message): ?>
                <tr>
                    <td><?= e(cms_format_date($message['CreatedDate'])) ?></td>
                    <td><?= e(trim($message['Name'] . ' ' . ($message['Surname'] ?? ''))) ?></td>
                    <td><?= e(enquiry_types()[$message['EnquiryType']] ?? $message['EnquiryType']) ?></td>
                    <td class="admin-table__actions"><a class="btn btn--outline btn--small" href="<?= e(url('admin/enquiry.php?id=' . (int) $message['MessageID'])) ?>">Open</a></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($draftPosts !== []): ?>
    <h2 class="admin-section-title">Blog drafts waiting to be published</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th scope="col">Title</th><th scope="col">Author</th><th scope="col">Last changed</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
<?php foreach ($draftPosts as $draft): ?>
                <tr>
                    <td><?= e($draft['Title']) ?></td>
                    <td><?= e(trim((string) $draft['AuthorName']) ?: 'A former user') ?></td>
                    <td><?= e(cms_format_date($draft['LastEditedDate'] ?? $draft['CreatedDate'])) ?></td>
                    <td class="admin-table__actions"><a class="btn btn--outline btn--small" href="<?= e(url('admin/blog-edit.php?id=' . (int) $draft['BlogID'])) ?>">Review<span class="visually-hidden"> <?= e($draft['Title']) ?></span></a></td>
                </tr>
<?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if (can('orders.view')): ?>
    <h2 class="admin-section-title">Coming soon</h2>
    <div class="admin-empty-grid">
        <?= admin_empty_state('No orders yet', 'Photography orders will appear here.', 'camera') ?>
    </div>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
