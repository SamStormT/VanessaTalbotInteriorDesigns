<?php
/**
 * History and undo for one record: every saved change, with what it was
 * before and after, and "Restore" to put the earlier values back.
 *
 * A restore is saved like any other change, so it can itself be undone.
 * Only fields the person's role may edit are restored (for example an
 * Editor never changes a Status), and a picture is only restored if it is
 * still in the Media Library.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';
require_once INCLUDES_PATH . '/blog.php';

$table = is_string($_GET['table'] ?? null) ? $_GET['table'] : '';
$id    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

// Which module owns the table, and where its edit screen is.
$module = null;
foreach (cms_modules() as $candidate) {
    if ($candidate['table'] === $table) {
        $module = $candidate;
    }
}
if ($table === 'NewsletterCampaigns') {
    $module = campaign_module();
}
if ($module === null || $id <= 0) {
    require_admin();
    admin_stop(404, 'Not found', 'There is no history for that.');
}

admin_guard($module['permission'] . '.edit');

$row = cms_load($module, $id);
if ($row === null) {
    admin_stop(404, 'Not found', 'That record no longer exists.');
}
if ($table === 'PageSections') {
    $module = cms_section_module($row['PageKey'], $row['SectionKey']);
}

$editUrl = url(match ($table) {
    'WebsiteSettings', 'HomePageContent', 'AboutContent', 'NewsletterSettings' => $module['page'] . '?edit=1',
    'PageSections'      => 'admin/section-edit.php?id=' . $id,
    'PageSectionItems'  => 'admin/item-edit.php?id=' . $id,
    'Services'          => 'admin/service-edit.php?id=' . $id,
    'ProjectImages'     => 'admin/project-image-edit.php?id=' . $id,
    'MediaLibrary'      => 'admin/media-edit.php?id=' . $id,
    'NewsletterCampaigns' => 'admin/campaign-edit.php?id=' . $id,
    default             => ($module['edit_page'] ?? 'admin/dashboard.php') . '?id=' . $id,
});
$self = url('admin/history.php?table=' . rawurlencode($table) . '&id=' . $id);

// Campaigns that have been sent can't change.
$locked = $table === 'NewsletterCampaigns' && !in_array($row['Status'], ['Draft', 'Ready'], true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $versionId = filter_input(INPUT_POST, 'version', FILTER_VALIDATE_INT) ?: 0;
    $version   = db_row('SELECT * FROM ContentVersions WHERE VersionID = ? AND TableName = ? AND RecordID = ?', [$versionId, $table, $id]);
    if ($version === null || $locked) {
        flash_set('error', $locked ? 'This campaign has been sent, so it can no longer change.' : 'That version no longer exists.');
        header('Location: ' . $self);
        exit;
    }

    $data    = [];
    $skipped = [];
    foreach (json_decode($version['OldValues'], true) ?: [] as $field => $value) {
        $definition = $module['fields'][$field] ?? null;
        if ($definition === null || !cms_field_editable($definition)) {
            $skipped[] = $definition['label'] ?? $field;
            continue;
        }
        if ($definition['type'] === 'image' && (string) $value !== ''
            && (int) db_value('SELECT COUNT(*) FROM MediaLibrary WHERE FilePath = ?', [$value]) === 0) {
            $skipped[] = $definition['label'] . ' (that picture has been deleted)';
            continue;
        }
        $data[$field] = $value;
    }

    $changed = cms_save($module, $id, $data, $row, 'Restored the version from ' . cms_format_date($version['ChangedDate']));
    if ($table === 'BlogPosts' && isset($data['Status'])) {
        blog_after_save($id, (string) $row['Status'], (string) $data['Status']);
    }
    if ($table === 'NewsletterCampaigns' && $changed !== [] && $row['Status'] === 'Ready') {
        db_query("UPDATE NewsletterCampaigns SET Status = 'Draft' WHERE CampaignID = ?", [$id]);
    }
    if ($changed !== []) {
        activity_log('restore_version', $table, $id, 'Version ' . $versionId);
    }

    flash_set($changed === [] ? 'info' : 'success', ($changed === [] ? 'Nothing to restore: it already matches that version.' : 'Restored. You can undo this too: it is now the newest change below.')
        . ($skipped !== [] ? ' Not restored: ' . implode(', ', $skipped) . '.' : ''));
    header('Location: ' . $self);
    exit;
}

$versions = db_all(
    "SELECT v.*, CONCAT(u.Name, ' ', u.Surname) AS ChangedByName
     FROM ContentVersions v LEFT JOIN AdminUsers u ON u.AdminUserID = v.ChangedBy
     WHERE v.TableName = ? AND v.RecordID = ? ORDER BY v.VersionID DESC",
    [$table, $id]
);

$label = static fn(string $field): string => $module['fields'][$field]['label'] ?? $field;

/** A stored value, readable: pictures as thumbnails, long text shortened, empty shown as such. */
$show = static function (string $field, mixed $value) use ($module): string {
    $value = (string) ($value ?? '');
    if ($value === '') {
        return '<span class="admin-muted">(empty)</span>';
    }
    $type = $module['fields'][$field]['type'] ?? 'text';
    if ($type === 'image') {
        return '<img class="admin-thumb admin-thumb--small" src="' . e(image_url($value, 'thumb')) . '" alt=""> <span class="admin-muted">' . e(basename($value)) . '</span>';
    }
    if ($type === 'checkbox') {
        return $value === '1' ? 'Yes' : 'No';
    }
    $text = mb_strlen($value) > 600 ? mb_substr($value, 0, 600) . '…' : $value;
    return '<span class="history-text">' . nl2br(e($text)) . '</span>';
};

$title     = (string) ($row[$module['label'] ?? ''] ?? '') ?: ($row['Heading'] ?? '') ?: $module['title'];
$pageTitle = 'History: ' . $title;
$layout    = 'admin';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($editUrl) ?>">Back to editing</a>
<?php if ($versions !== [] && !$locked): ?>
        <form method="post" data-confirm="Undo the most recent change (<?= e(implode(', ', array_map($label, array_keys(json_decode($versions[0]['NewValues'], true) ?: [])))) ?>)?">
            <?= csrf_field() ?><input type="hidden" name="version" value="<?= (int) $versions[0]['VersionID'] ?>">
            <button class="btn btn--primary btn--small" type="submit">Undo the last change</button>
        </form>
<?php endif; ?>
    </div>
    <p class="admin-help">Every saved change is listed, newest first, with what each field was before and after. "Restore" puts back the values from <em>before</em> that change. Restoring is saved as a new change, so it can be undone too. The last <?= CMS_VERSIONS_KEPT ?> changes are kept.</p>

<?php if ($versions === []): ?>
    <?= admin_empty_state('No changes yet', 'Changes appear here after the first save.', 'pen') ?>
<?php endif; ?>

<?php foreach ($versions as $index => $version):
    $old = json_decode($version['OldValues'], true) ?: [];
    $new = json_decode($version['NewValues'], true) ?: [];
?>
    <section class="admin-panel history-entry">
        <div class="history-entry__head">
            <h2 class="admin-panel__title"><?= e(cms_format_date($version['ChangedDate'])) ?><?= $index === 0 ? ' <span class="admin-badge">Latest</span>' : '' ?></h2>
            <p class="admin-meta"><?= e(trim((string) $version['ChangedByName']) ?: 'A former user') ?> changed <?= e(implode(', ', array_map($label, array_keys($new)))) ?><?= $version['Note'] ? ' &middot; <em>' . e($version['Note']) . '</em>' : '' ?></p>
<?php if (!$locked): ?>
            <form method="post" data-confirm="Put back the values from before this change?">
                <?= csrf_field() ?><input type="hidden" name="version" value="<?= (int) $version['VersionID'] ?>">
                <button class="btn btn--outline btn--small" type="submit">Restore what it was before</button>
            </form>
<?php endif; ?>
        </div>
        <details class="history-entry__details"<?= $index === 0 ? ' open' : '' ?>>
            <summary>Show before and after</summary>
            <div class="admin-table-wrap">
                <table class="admin-table history-table">
                    <thead><tr><th scope="col">Field</th><th scope="col">Before</th><th scope="col">After</th></tr></thead>
                    <tbody>
<?php foreach ($new as $field => $after): ?>
                        <tr><th scope="row"><?= e($label($field)) ?></th><td data-label="Before"><?= $show($field, $old[$field] ?? null) ?></td><td data-label="After"><?= $show($field, $after) ?></td></tr>
<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
<?php endforeach; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
