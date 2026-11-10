<?php
/**
 * Contact Messages: one message, with its status and private notes.
 * Replies are sent from the business mailbox (the Reply button opens an
 * email to the sender); this screen records where each enquiry stands.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/enquiries.php';
require_once INCLUDES_PATH . '/content.php';

$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$deleting = $posted && isset($_POST['_delete']);

admin_guard($deleting ? 'messages.delete' : ($posted ? 'messages.edit' : 'messages.view'));

$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$row = $id > 0 ? db_row('SELECT * FROM ContactMessages WHERE MessageID = ?', [$id]) : null;

if ($row === null) {
    admin_stop(404, 'Message not found', 'That message does not exist.');
}

$statuses = enquiry_statuses();
$self     = url('admin/enquiry.php?id=' . $id);
$list     = url('admin/enquiries.php');
$name     = trim($row['Name'] . ' ' . ($row['Surname'] ?? ''));

if ($deleting) {
    db()->beginTransaction();
    db_query('DELETE FROM ContactMessages WHERE MessageID = ?', [$id]);
    activity_log('delete', 'ContactMessages', $id);
    db()->commit();
    flash_set('success', 'Message deleted.');
    header('Location: ' . $list);
    exit;
}

$fields = [
    'Status'        => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => array_keys($statuses), 'option_labels' => $statuses],
    'InternalNotes' => ['label' => 'Private notes', 'type' => 'textarea', 'max' => 5000, 'help' => 'Only staff see these notes. They are never sent to the person who wrote in.'],
];
$errors = [];

if ($posted) {
    $status = is_string($_POST['Status'] ?? null) ? $_POST['Status'] : '';
    $notes  = form_multiline($_POST, 'InternalNotes');

    if (!isset($statuses[$status])) {
        $errors['Status'] = 'Please choose a status.';
    }
    if (mb_strlen($notes) > 5000) {
        $errors['InternalNotes'] = 'Please use 5,000 characters or fewer.';
    }

    if ($errors === []) {
        $notes   = $notes === '' ? null : $notes;
        $changed = array_keys(array_filter(['Status' => $status !== $row['Status'], 'InternalNotes' => $notes !== $row['InternalNotes']]));

        if ($changed !== []) {
            db()->beginTransaction();
            db_query(
                "UPDATE ContactMessages
                 SET Status = ?, InternalNotes = ?,
                     RespondedDate = IF(? = 'Responded' AND RespondedDate IS NULL, NOW(), RespondedDate)
                 WHERE MessageID = ?",
                [$status, $notes, $status, $id]
            );
            activity_log('update', 'ContactMessages', $id, 'Changed: ' . implode(', ', $changed));
            db()->commit();
        }
        flash_set('success', $changed === [] ? 'No changes to save.' : 'Saved.');
        header('Location: ' . $self);
        exit;
    }
}

$subscriber = $row['SubscribeToNewsletter']
    ? db_row('SELECT SubscriptionStatus, ConfirmedDate FROM Subscribers WHERE Email = ?', [strtolower($row['Email'])])
    : null;
$newsletter = match (true) {
    !$row['SubscribeToNewsletter']                      => '',
    $subscriber === null                                => 'Asked to subscribe',
    $subscriber['SubscriptionStatus'] !== 'Subscribed'  => 'Asked to subscribe, but has since unsubscribed',
    $subscriber['ConfirmedDate'] === null               => 'Asked to subscribe; waiting for them to confirm by email',
    default                                             => 'Subscribed and confirmed',
};

$replySubject = 'Re: ' . ($row['Subject'] ?: 'Your enquiry to ' . SITE_NAME);
$replyLink    = 'mailto:' . rawurlencode($row['Email']) . '?subject=' . rawurlencode($replySubject);

$pageTitle = 'Message from ' . $name;
$layout    = 'admin';
$activeNav = 'admin/enquiries.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($list) ?>">All messages</a>
        <a class="btn btn--primary btn--small" href="<?= e($replyLink) ?>">Reply by email</a>
    </div>

    <section class="admin-panel">
        <h2 class="admin-panel__title">Details</h2>
        <dl class="admin-fields">
            <div><dt>From</dt><dd><?= e($name) ?></dd></div>
            <div><dt>Email</dt><dd><a href="<?= e($replyLink) ?>"><?= e($row['Email']) ?></a></dd></div>
<?php if ($row['Phone']): ?>
            <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $row['Phone'])) ?>"><?= e($row['Phone']) ?></a></dd></div>
<?php endif; ?>
            <div><dt>Enquiry about</dt><dd><?= e(enquiry_types()[$row['EnquiryType']] ?? $row['EnquiryType']) ?></dd></div>
<?php if ($row['Subject']): ?>
            <div><dt>Subject</dt><dd><?= e($row['Subject']) ?></dd></div>
<?php endif; ?>
            <div><dt>Received</dt><dd><?= e(cms_format_date($row['CreatedDate'])) ?></dd></div>
<?php if ($row['RespondedDate']): ?>
            <div><dt>Marked responded</dt><dd><?= e(cms_format_date($row['RespondedDate'])) ?></dd></div>
<?php endif; ?>
<?php if ($newsletter !== ''): ?>
            <div><dt>Newsletter</dt><dd><?= e($newsletter) ?></dd></div>
<?php endif; ?>
        </dl>
    </section>

    <section class="admin-panel">
        <h2 class="admin-panel__title">Message</h2>
        <div class="admin-message"><?= content_paragraphs($row['Message']) ?></div>
    </section>

<?php if (can('messages.edit')): ?>
    <form method="post" class="admin-form" novalidate>
        <?= csrf_field() ?>
<?php if ($errors !== []): ?>
        <div class="admin-alert admin-alert--error" role="alert">Please correct the highlighted fields.</div>
<?php endif; ?>
        <fieldset class="admin-panel">
            <legend class="admin-panel__title">Follow-up</legend>
<?php foreach ($fields as $field => $definition): ?>
            <?= cms_render_control($field, $definition, $posted ? (is_string($_POST[$field] ?? null) ? $_POST[$field] : '') : (string) ($row[$field] ?? ''), $errors[$field] ?? null) ?>
<?php endforeach; ?>
        </fieldset>
        <div class="admin-actions">
            <button class="btn btn--primary" type="submit">Save changes</button>
            <a class="btn btn--outline" href="<?= e($list) ?>">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php if (can('messages.delete')): ?>
    <?= cms_delete_form('this message from ' . $name) ?>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
