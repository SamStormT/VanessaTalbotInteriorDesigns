<?php
/**
 * Blog Posts: write a new post, or edit one.
 *
 * Publishing uses plain buttons instead of a status list: Save draft,
 * Publish now and Schedule (a date and time). Once live: Save changes and
 * Take off the website (archive). Editors can't publish: they save drafts
 * and can ask the Admins to publish, who are emailed.
 *
 * After publishing or scheduling, the screen offers to turn the post into a
 * newsletter. The text is kept in the browser while typing (see admin.js),
 * so a closed tab or lost connection doesn't lose it.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/blog.php';

$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard('blogs.' . ($deleting ? 'delete' : ($creating ? 'create' : 'edit')));

$module = blog_post_module();
$id     = $creating ? 0 : (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
$row    = [];
if (!$creating) {
    $row = db_row(
        "SELECT p.*, CONCAT(a.Name, ' ', a.Surname) AS AuthorName, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
         FROM BlogPosts p
         LEFT JOIN AdminUsers a ON a.AdminUserID = p.AuthorID
         LEFT JOIN AdminUsers u ON u.AdminUserID = p.LastEditedBy
         WHERE p.BlogID = ?",
        [$id]
    );
    if ($row === null) {
        admin_stop(404, 'Post not found', 'That blog post does not exist.');
    }
}

$list       = url('admin/blogs.php');
$canPublish = can('blogs.publish');
$current    = $creating ? ['Status' => 'Draft', 'PublishedDate' => null] : $row;

if ($deleting && !$creating) {
    cms_delete($module, $id);
    flash_set('success', 'Post deleted.');
    header('Location: ' . $list);
    exit;
}

$errors        = [];
$scheduleError = null;
if ($posted) {
    $action = is_string($_POST['publish_action'] ?? null) ? $_POST['publish_action'] : 'save';
    $input  = $_POST;

    // The buttons decide the status and publish date; the form has no fields for them.
    if ($canPublish) {
        $date = (string) ($current['PublishedDate'] ?? '');
        [$input['Status'], $input['PublishedDate']] = match ($action) {
            'publish'  => ['Published', $date !== '' && strtotime($date) <= time() ? $date : ''], // blank: stamped now
            'schedule' => ['Published', is_string($_POST['schedule_at'] ?? null) ? $_POST['schedule_at'] : ''],
            'draft'    => ['Draft', $date],
            'archive'  => ['Archived', $date],
            default    => [(string) $current['Status'], $date],
        };
        if ($action === 'schedule') {
            $when = cms_datetime($input['PublishedDate']);
            if ($when === null || strtotime($when) <= time() + 60) {
                $scheduleError = 'Choose a date and time still to come.';
            }
        }
    }

    [$data, $errors] = cms_validate($module, $input, $row);
    if ($scheduleError !== null) {
        $errors['_form'] = 'The post was not saved: ' . lcfirst($scheduleError);
    }
    if (array_key_exists('CategoryID', $data)) {
        $data['CategoryID'] = $data['CategoryID'] === null ? null : (int) $data['CategoryID'];
    }

    if ($errors === []) {
        try {
            $wasLive = !$creating && blog_is_live($row);
            if ($creating) {
                $id = cms_create($module, $data + ['AuthorID' => (int) current_admin()['AdminUserID']]);
                blog_after_save($id, 'Draft', (string) ($data['Status'] ?? 'Draft'));
                $message = blog_save_message($id);
            } else {
                $changed = cms_save($module, $id, $data, $row);
                blog_after_save($id, (string) $row['Status'], (string) ($data['Status'] ?? $row['Status']));
                $message = $changed === [] ? 'No changes to save.' : blog_save_message($id, $row);
            }
            media_learn_alt_text($module, $data);

            if ($action === 'review' && !$canPublish) {
                $message = blog_request_publishing($id) ? 'Saved, and the Admins have been asked to publish it.' : 'Saved. No Admin could be emailed, so please let them know it is ready.';
            }
            flash_set('success', $message);

            // Just published or scheduled: offer the newsletter next.
            $saved = db_row('SELECT Status, PublishedDate FROM BlogPosts WHERE BlogID = ?', [$id]);
            $next  = match (true) {
                !$wasLive && blog_is_live($saved)                                          => '&next=published',
                blog_is_scheduled($saved) && in_array($action, ['schedule'], true)         => '&next=scheduled',
                default                                                                     => '',
            };
            header('Location: ' . url('admin/blog-edit.php?id=' . $id . $next));
            exit;
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $errors['Slug'] = 'Another post already uses this web address. Change the title, or the web address under More options.';
        }
    }
}

$next      = is_string($_GET['next'] ?? null) ? $_GET['next'] : '';
$live      = !$creating && blog_is_live($row);
$scheduled = !$creating && blog_is_scheduled($row);

$pageTitle = $creating ? 'New blog post' : 'Edit: ' . $row['Title'];
$layout    = 'admin';
$activeNav = 'admin/blogs.php';

// The buttons under the form, for this post's state and the person's role.
$button = static fn(string $action, string $label, string $style = 'outline'): string =>
    '<button class="btn btn--' . $style . '" type="submit" name="publish_action" value="' . $action . '">' . $label . '</button>';
$scheduleBox = static function (string $label) use ($row, $scheduleError): string {
    $value = isset($_POST['schedule_at']) && is_string($_POST['schedule_at']) ? $_POST['schedule_at']
        : (blog_is_scheduled($row) ? str_replace(' ', 'T', substr((string) $row['PublishedDate'], 0, 16)) : '');
    return '<details class="publish-schedule"' . ($scheduleError !== null ? ' open' : '') . '><summary class="btn btn--outline">' . $label . '</summary>'
         . '<div class="publish-schedule__box"><div class="admin-field"><label for="schedule-at">Publish on</label>'
         . '<input id="schedule-at" name="schedule_at" type="datetime-local" value="' . e($value) . '" min="' . e(date('Y-m-d\TH:i', time() + 300)) . '" aria-describedby="schedule-help' . ($scheduleError !== null ? ' schedule-error' : '') . '"' . ($scheduleError !== null ? ' aria-invalid="true"' : '') . '>'
         . ($scheduleError !== null ? '<p class="admin-error" id="schedule-error">' . e($scheduleError) . '</p>' : '')
         . '<p class="admin-help" id="schedule-help">South African time. The post stays hidden until then and appears by itself.</p></div>'
         . '<button class="btn btn--primary" type="submit" name="publish_action" value="schedule">Schedule</button></div></details>';
};

if (!$canPublish) {
    $buttons = $live
        ? $button('save', 'Save changes', 'primary')
        : $button('save', 'Save draft', 'primary') . $button('review', 'Save and ask an Admin to publish');
} elseif ($creating || $row['Status'] === 'Draft') {
    $buttons = $button('draft', 'Save draft') . $button('publish', 'Publish now', 'primary') . $scheduleBox('Schedule…');
} elseif ($scheduled) {
    $buttons = $button('save', 'Save changes', 'primary') . $button('publish', 'Publish now instead') . $scheduleBox('Change the time…') . $button('draft', 'Back to draft');
} elseif ($row['Status'] === 'Archived') {
    $buttons = $button('save', 'Save changes') . $button('publish', 'Publish again', 'primary');
} else {
    $buttons = $button('save', 'Save changes', 'primary') . $button('archive', 'Take off the website');
}

$form = cms_render_form($module, $creating ? ['Status' => 'Draft'] : $row, $errors, $list, $posted);
$form = str_replace('<form method="post" class="admin-form" novalidate>', '<form method="post" class="admin-form" novalidate data-autosave="blog-' . ($creating ? 'new' : $id) . '" data-autosave-saved="' . e((string) ($row['LastEditedDate'] ?? $row['UpdatedDate'] ?? '')) . '">', $form);
$form = str_replace('<div class="admin-actions"><button class="btn btn--primary" type="submit">Save changes</button>', '<div class="admin-actions publish-actions">' . $buttons, $form);

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>

<?php if (!$creating && $next === 'published' && $live): ?>
    <section class="admin-panel publish-next" aria-labelledby="next-title">
        <h2 class="admin-panel__title" id="next-title">Your article is on the website</h2>
<?php if (can('subscribers.create')): ?>
        <p>Would you like to send it to your subscribers as a newsletter? Everything is filled in from the article; you add a short introduction.</p>
        <p class="admin-actions">
            <a class="btn btn--primary" href="<?= e(url('admin/campaign-edit.php?from_post=' . $id)) ?>">Create a newsletter from it</a>
            <a class="btn btn--outline" href="<?= e(blog_post_url((string) $row['Slug'])) ?>" target="_blank" rel="noopener">View it<span class="visually-hidden"> (opens in a new tab)</span></a>
            <a class="btn btn--outline" href="<?= e(url('admin/blog-edit.php?id=' . $id)) ?>">Not now</a>
        </p>
<?php else: ?>
        <p><a href="<?= e(blog_post_url((string) $row['Slug'])) ?>" target="_blank" rel="noopener">View it on the website<span class="visually-hidden"> (opens in a new tab)</span></a></p>
<?php endif; ?>
    </section>
<?php elseif (!$creating && $next === 'scheduled' && $scheduled && can('subscribers.create')): ?>
    <section class="admin-panel publish-next" aria-labelledby="next-title">
        <h2 class="admin-panel__title" id="next-title">Scheduled for <?= e(date('l j F \a\t H:i', strtotime((string) $row['PublishedDate']))) ?></h2>
        <p>You can prepare the newsletter now and schedule it for just after the article appears.</p>
        <p class="admin-actions">
            <a class="btn btn--primary" href="<?= e(url('admin/campaign-edit.php?from_post=' . $id)) ?>">Prepare the newsletter</a>
            <a class="btn btn--outline" href="<?= e(url('admin/blog-edit.php?id=' . $id)) ?>">Not now</a>
        </p>
    </section>
<?php endif; ?>

    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($list) ?>">All posts</a>
<?php if (!$creating): ?>
        <?= cms_history_link($module, $id) ?>
        <a class="btn btn--outline btn--small" href="<?= e(blog_post_url((string) $row['Slug'])) ?>" target="_blank" rel="noopener"><?= $live ? 'View on website' : 'Preview' ?><span class="visually-hidden"> (opens in a new tab)</span></a>
<?php if ($row['Status'] === 'Published' && can('subscribers.create') && $next === ''): ?>
        <a class="btn btn--outline btn--small" href="<?= e(url('admin/campaign-edit.php?from_post=' . (int) $row['BlogID'])) ?>">Create a newsletter from it</a>
<?php endif; ?>
<?php endif; ?>
    </div>

<?php if (!$creating): ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Post</h2>
        <dl class="admin-fields">
            <div><dt>Status</dt><dd><span class="admin-badge admin-badge--<?= e(strtolower(blog_status_label($row))) ?>"><?= e(blog_status_label($row)) ?></span><?= $scheduled ? ' <span class="admin-muted">Appears on the website by itself on ' . e(cms_format_date($row['PublishedDate'])) . '. Until then only signed-in staff can see it.</span>' : ($live ? '' : ' <span class="admin-muted">Only signed-in staff can see it.</span>') ?></dd></div>
            <div><dt>Author</dt><dd><?= e(trim((string) $row['AuthorName']) ?: 'A former user') ?></dd></div>
            <div><dt><?= $scheduled ? 'Scheduled for' : 'Published' ?></dt><dd><?= $row['PublishedDate'] && $row['Status'] !== 'Draft' ? e(cms_format_date($row['PublishedDate'])) : '<span class="admin-muted">Not yet</span>' ?></dd></div>
            <div><dt>Last edited</dt><dd><?= cms_last_edited_cell($row) ?></dd></div>
        </dl>
    </section>
<?php endif; ?>

<?php if (!$canPublish): ?>
    <p class="admin-help">You can write and edit posts. An Admin publishes them: when it's ready, choose <strong>Save and ask an Admin to publish</strong>.<?= $live ? ' This post is already published, so your changes appear on the website as soon as you save.' : '' ?></p>
<?php endif; ?>

    <?= $form ?>

<?php if (!$creating && can('blogs.delete')): ?>
    <?= cms_delete_form((string) $row['Title']) ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
