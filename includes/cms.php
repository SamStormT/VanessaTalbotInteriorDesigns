<?php
/**
 * CMS framework: turns a definition in cms-modules.php into a read-only view,
 * an edit form, validation, saving, LastEdited stamps and an activity entry.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms-modules.php';
require_once INCLUDES_PATH . '/cms-records.php';
require_once INCLUDES_PATH . '/media.php';

function cms_module(string $key): array
{
    $module = cms_modules()[$key] ?? throw new InvalidArgumentException('Unknown CMS module.');
    // Lists filled from the database, for example the services a testimonial can belong to.
    foreach ($module['fields'] as $name => $field) {
        if (isset($field['choices'])) {
            $labels = cms_choices($field['choices']);
            $module['fields'][$name]['options']       = array_map('strval', array_keys($labels));
            $module['fields'][$name]['option_labels'] = $labels;
        }
    }
    return $module;
}

/** Choices for a list field: value => label. */
function cms_choices(string $source): array
{
    static $cache = [];
    if (isset($cache[$source])) {
        return $cache[$source];
    }
    $rows = match ($source) {
        'services' => db_all('SELECT Slug AS Value, ServiceName AS Label FROM Services ORDER BY DisplayOrder, ServiceName'),
        'projects' => db_all('SELECT ProjectID AS Value, Title AS Label FROM PortfolioProjects ORDER BY Title'),
        default    => [],
    };
    return $cache[$source] = array_column($rows, 'Label', 'Value');
}

/** Can the current user change this field? */
function cms_field_editable(array $field): bool
{
    return empty($field['permission']) || can($field['permission']);
}

function cms_load(array $module, int $id): ?array
{
    return db_row(
        "SELECT t.*, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
         FROM `{$module['table']}` t
         LEFT JOIN AdminUsers u ON u.AdminUserID = t.LastEditedBy
         WHERE t.`{$module['key']}` = ?",
        [$id]
    );
}

/** Check submitted values. Returns [clean data, errors keyed by field name]. */
function cms_validate(array $module, array $input, array $existing = []): array
{
    $data   = [];
    $errors = [];

    foreach ($module['fields'] as $name => $field) {
        if (!cms_field_editable($field)) {
            continue;
        }

        if ($field['type'] === 'checkbox') {
            $data[$name] = isset($input[$name]) ? 1 : 0;
            continue;
        }

        // A date and time that wasn't sent at all is left as it is: blank
        // means "now" for a publish date, so it must be cleared on purpose.
        if ($field['type'] === 'datetime' && !array_key_exists($name, $input)) {
            continue;
        }

        $raw   = $input[$name] ?? '';
        $value = is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '';
        $label = $field['label'];

        // Text pasted from some old programs isn't UTF-8; refuse it clearly instead of failing to save.
        if (!mb_check_encoding($value, 'UTF-8')) {
            $errors[$name] = "$label contains characters that couldn't be read. Please retype any special characters, such as dashes or quotes.";
            continue;
        }

        if ($value === '') {
            if (!empty($field['required'])) {
                $errors[$name] = "$label is required.";
            }
            $data[$name] = $field['type'] === 'int' && empty($field['nullable']) ? 0 : null;
            continue;
        }

        // A website typed without https:// (vtid.co.za, www.vtid.co.za) gets it added.
        if ($field['type'] === 'url' && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $value) && preg_match('#^[a-z0-9-]+(\.[a-z0-9-]+)+([/?\#].*)?$#i', $value)) {
            $value = 'https://' . $value;
        }

        if (isset($field['max']) && mb_strlen($value) > $field['max']) {
            $errors[$name] = "$label can be at most {$field['max']} characters.";
        }

        $problem = match ($field['type']) {
            'email'  => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.',
            'tel'    => preg_match('/^\+?[0-9][0-9 ()-]{5,28}$/', $value) ? null : 'Enter a valid phone number.',
            'url'    => preg_match('#^https?://[^\s<>"]+$#i', $value) && filter_var($value, FILTER_VALIDATE_URL) ? null : 'Enter a website, for example vtid.co.za or https://vtid.co.za',
            'link'   => link_is_safe($value) ? null : 'Enter a page such as contact.php, or a full address starting with https://',
            'image'  => $value === (string) ($existing[$name] ?? '') || (int) db_value('SELECT COUNT(*) FROM MediaLibrary WHERE FilePath = ?', [$value]) > 0 ? null : 'Please upload an image or choose one from the library.',
            'slug'   => preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value) ? null : 'Use lower-case letters, numbers and single hyphens only.',
            'int'    => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : 'Enter a whole number.',
            'date'   => (DateTime::createFromFormat('!Y-m-d', $value) ?: null)?->format('Y-m-d') === $value ? null : 'Enter a valid date.',
            'datetime' => cms_datetime($value) !== null ? null : 'Enter a valid date and time.',
            'select' => in_array($value, $field['options'], true) ? null : 'Choose one of the listed options.',
            default  => null,
        };
        if ($problem !== null) {
            $errors[$name] = $problem;
        }

        $data[$name] = match ($field['type']) {
            'int'      => (int) $value,
            'datetime' => cms_datetime($value) ?? $value,
            default    => $value,
        };
    }

    // A blank slug is built from the field it names, for example the title.
    foreach ($module['fields'] as $name => $field) {
        if (!empty($field['auto_from']) && array_key_exists($name, $data) && $data[$name] === null) {
            $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) ($data[$field['auto_from']] ?? ''))), '-');
            $data[$name] = $slug === '' ? 'item' : $slug;
        }
    }

    // Some records need permission on file before they can be published.
    if (isset($module['publish_requires'])) {
        [$field, $message] = $module['publish_requires'];
        $status = $data['Status'] ?? ($existing['Status'] ?? 'Draft');
        $value  = array_key_exists($field, $data) ? $data[$field] : ($existing[$field] ?? null);
        if ($status === 'Published' && empty($value)) {
            $errors[$field] = $message;
        }
    }

    return [$data, $errors];
}

/**
 * A date and time as the database stores it (2026-11-16 09:30:00), from the
 * browser's date and time box (2026-11-16T09:30) or the database itself.
 * Null if it isn't a real date and time.
 */
function cms_datetime(?string $value): ?string
{
    $value = trim((string) $value);
    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value);
        if ($date !== false && $date->format($format) === $value) {
            return $date->format('Y-m-d H:i:00');
        }
    }
    return null;
}

/**
 * Save changes, stamp LastEditedBy/LastEditedDate and write the activity log.
 * Returns the names of the fields that changed.
 */
function cms_save(array $module, int $id, array $data, array $existing, ?string $note = null): array
{
    $changed = [];
    foreach ($data as $name => $value) {
        $before = (string) ($existing[$name] ?? '');
        $after  = (string) ($value ?? '');
        // Date and time boxes show minutes, not seconds: the same minute is no change.
        if (($module['fields'][$name]['type'] ?? '') === 'datetime' && $before !== '' && $after !== '') {
            [$before, $after] = [substr($before, 0, 16), substr($after, 0, 16)];
        }
        if ($before !== $after) {
            $changed[$name] = $value;
        }
    }
    if ($changed === []) {
        return [];
    }

    $assignments = implode(', ', array_map(static fn(string $name): string => "`$name` = ?", array_keys($changed)));
    $admin       = current_admin();

    db()->beginTransaction();
    try {
        db_query(
            "UPDATE `{$module['table']}` SET $assignments, LastEditedBy = ?, LastEditedDate = NOW() WHERE `{$module['key']}` = ?",
            [...array_values($changed), $admin['AdminUserID'], $id]
        );
        // Field names only: values could contain personal data.
        activity_log('update', $module['table'], $id, 'Changed: ' . implode(', ', array_keys($changed)));
        try {
            cms_version_record($module['table'], $id, array_intersect_key($existing, $changed), $changed, (int) $admin['AdminUserID'], $note);
        } catch (PDOException $error) {
            // History must never stop a save (for example before migration 015 has run).
            error_log('Version history not recorded for ' . $module['table'] . ' ' . $id . ': ' . $error->getMessage());
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }

    return array_keys($changed);
}

// ---------------------------------------------------------------------------
// Version history: every save keeps the previous values, so it can be undone.
// ---------------------------------------------------------------------------

const CMS_VERSIONS_KEPT = 50;

/** Keep the before and after values of the fields a save changed. */
function cms_version_record(string $table, int $id, array $old, array $new, ?int $adminId, ?string $note = null): void
{
    foreach (array_keys($new) as $field) {
        $old[$field] ??= null;
    }
    db_query(
        'INSERT INTO ContentVersions (TableName, RecordID, OldValues, NewValues, ChangedBy, Note) VALUES (?, ?, ?, ?, ?, ?)',
        [$table, $id, json_encode($old, JSON_UNESCAPED_UNICODE), json_encode($new, JSON_UNESCAPED_UNICODE), $adminId, $note]
    );
    // Keep the newest versions only.
    $cutoff = db_value(
        'SELECT VersionID FROM ContentVersions WHERE TableName = ? AND RecordID = ? ORDER BY VersionID DESC LIMIT 1 OFFSET ' . CMS_VERSIONS_KEPT,
        [$table, $id]
    );
    if ($cutoff !== null) {
        db_query('DELETE FROM ContentVersions WHERE TableName = ? AND RecordID = ? AND VersionID <= ?', [$table, $id, $cutoff]);
    }
}

/** A "History" link for a record's edit screen, when it has any saved changes. */
function cms_history_link(array $module, int $id): string
{
    try {
        $count = (int) db_value('SELECT COUNT(*) FROM ContentVersions WHERE TableName = ? AND RecordID = ?', [$module['table'], $id]);
    } catch (Throwable $error) {
        return ''; // version table not created yet
    }
    if ($count === 0 || !can($module['permission'] . '.edit')) {
        return '';
    }
    return '<a class="btn btn--outline btn--small" href="' . e(url('admin/history.php?table=' . rawurlencode($module['table']) . '&id=' . $id)) . '">History and undo <span class="admin-filter__count">' . $count . '</span></a>';
}

/**
 * Validate and save a POST. On success redirects with a message.
 * On failure returns the errors so the form can show them.
 */
function cms_handle_post(array $module, int $id, array $existing, string $redirect): array
{
    [$data, $errors] = cms_validate($module, $_POST, $existing);

    if ($errors === []) {
        try {
            $changed = cms_save($module, $id, $data, $existing);
            media_learn_alt_text($module, $data);
            flash_set('success', $changed === [] ? 'No changes to save.' : 'Your changes have been saved.');
            header('Location: ' . $redirect);
            exit;
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? 0) !== 1062) {
                throw $error;
            }
            $errors['_form'] = 'That name or web address is already used by another record.';
        }
    }

    return $errors;
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

function cms_flash(): string
{
    $flash = flash_take();
    if ($flash === null) {
        return '';
    }
    return '<p class="admin-alert admin-alert--' . e($flash['type']) . '" role="status">' . e($flash['message']) . '</p>';
}

function cms_format_date(?string $date): string
{
    return $date === null ? '' : date('j M Y, H:i', strtotime($date));
}

function cms_last_edited(array $row): string
{
    if (empty($row['LastEditedDate'])) {
        return '<p class="admin-meta">Not edited yet</p>';
    }
    $name = trim((string) ($row['LastEditorName'] ?? '')) ?: 'a former user';
    return '<p class="admin-meta">Last edited by <strong>' . e($name) . '</strong> on '
         . '<time datetime="' . e($row['LastEditedDate']) . '">' . e(cms_format_date($row['LastEditedDate'])) . '</time></p>';
}

function cms_group_fields(array $fields): array
{
    $groups = [];
    foreach ($fields as $name => $field) {
        $groups[$field['group'] ?? 'Details'][$name] = $field;
    }
    return $groups;
}

function cms_render_view(array $module, array $row): string
{
    $html = '';
    foreach (cms_group_fields($module['fields']) as $group => $fields) {
        $html .= '<section class="admin-panel"><h2 class="admin-panel__title">' . e($group) . '</h2><dl class="admin-fields">';
        foreach ($fields as $name => $field) {
            $value = $row[$name] ?? null;
            if ($field['type'] === 'image') {
                $html .= '<div><dt>' . e($field['label']) . '</dt><dd>' . ($value ? '<img class="admin-thumb" src="' . e(image_url((string) $value, 'thumb')) . '" alt="">' : '<span class="admin-muted">Not set</span>') . '</dd></div>';
                continue;
            }
            if ($field['type'] === 'checkbox') {
                $text = $value ? 'Yes' : 'No';
            } else {
                $text = (string) ($value ?? '');
            }
            $shown = $text === '' ? '<span class="admin-muted">Not set</span>' : nl2br(e($text));
            $html .= '<div><dt>' . e($field['label']) . '</dt><dd>' . $shown . '</dd></div>';
        }
        $html .= '</dl></section>';
    }
    return $html;
}

function cms_render_control(string $name, array $field, string $value, ?string $error): string
{
    $id        = 'field-' . $name;
    $describe  = [];
    $help      = '';
    $message   = '';

    if (!empty($field['help'])) {
        $describe[] = $id . '-help';
        $help = '<p class="admin-help" id="' . e($id) . '-help">' . e($field['help']) . '</p>';
    }
    if ($error !== null) {
        $describe[] = $id . '-error';
        $message = '<p class="admin-error" id="' . e($id) . '-error">' . e($error) . '</p>';
    }

    // Live character count, with a recommended length where it matters.
    $counter = '';
    if (in_array($field['type'], ['text', 'textarea'], true) && empty($field['rich']) && isset($field['max']) && $field['max'] <= 1000) {
        $ideal   = $field['ideal'] ?? cms_ideal_length($name);
        $describe[] = $id . '-count';
        $counter = '<p class="admin-count" id="' . e($id) . '-count" data-count-for="' . e($id) . '"'
                 . ($ideal ? ' data-ideal="' . (int) $ideal . '" data-ideal-why="' . e(cms_ideal_reason($name)) . '"' : '')
                 . ' aria-live="polite">' . mb_strlen($value) . ' characters' . ($ideal ? ' · aim for ' . (int) $ideal . ' or fewer' : '') . '</p>';
        $help = $counter . $help;
    }

    $attrs = 'id="' . e($id) . '" name="' . e($name) . '"';
    if ($describe !== []) {
        $attrs .= ' aria-describedby="' . e(implode(' ', $describe)) . '"';
    }
    if ($error !== null) {
        $attrs .= ' aria-invalid="true"';
    }
    if (!empty($field['required'])) {
        $attrs .= ' required';
    }

    if ($field['type'] === 'datetime' && ($stored = cms_datetime($value)) !== null) {
        $value = str_replace(' ', 'T', substr($stored, 0, 16)); // the browser's date and time box wants 2026-11-16T09:30
    }

    if ($field['type'] === 'image') {
        return cms_render_image_control($name, $field, $value, $id, $help, $message);
    }

    if ($field['type'] === 'checkbox') {
        $checked = $value !== '' && $value !== '0' ? ' checked' : '';
        return '<div class="admin-field admin-field--check" id="row-' . e($name) . '"><label><input type="checkbox" value="1" ' . $attrs . $checked . '> '
             . e($field['label']) . '</label>' . $help . $message . '</div>';
    }

    $label = '<label for="' . e($id) . '">' . e($field['label'])
           . (!empty($field['required']) ? ' <span class="required" aria-hidden="true">*</span>' : '') . '</label>';

    if ($field['type'] === 'select') {
        $control = '<select ' . $attrs . '>';
        if (empty($field['required'])) {
            $control .= '<option value="">None</option>';
        }
        foreach ($field['options'] as $option) {
            $control .= '<option value="' . e($option) . '"' . ($option === $value ? ' selected' : '') . '>' . e($field['option_labels'][$option] ?? $option) . '</option>';
        }
        $control .= '</select>';
    } elseif (!empty($field['rich'])) {
        $control = cms_rich_editor($attrs, $value, ($field['max'] ?? 0) > 20000 ? 18 : 8);
    } elseif (in_array($field['type'], ['textarea', 'lines'], true)) {
        $rows    = ($field['max'] ?? 0) > 5000 ? 8 : 4;
        $control = '<textarea rows="' . $rows . '" ' . $attrs . '>' . e($value) . '</textarea>';
    } else {
        $type = match ($field['type']) {
            'email' => 'email', 'tel' => 'tel', 'url' => 'url', 'int' => 'number', 'date' => 'date', 'datetime' => 'datetime-local', default => 'text',
        };
        $max     = isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '';
        $control = '<input type="' . $type . '" value="' . e($value) . '"' . $max . ' ' . $attrs . '>';
    }

    return '<div class="admin-field" id="row-' . e($name) . '">' . $label . $control . $help . $message . '</div>';
}

/**
 * Recommended length for fields where length affects how things look: search
 * results cut titles and descriptions short, buttons wrap, and headings that
 * run long crowd the design. A field definition can set 'ideal' instead.
 */
function cms_ideal_length(string $name): ?int
{
    return match (true) {
        $name === 'MetaTitle'                                  => 60,
        $name === 'MetaDescription'                            => 155,
        $name === 'Subject'                                    => 60,
        $name === 'PreviewText'                                => 100,
        (bool) preg_match('/(ButtonText|^CTA\dText|ReadMoreText)$/', $name) => 25,
        in_array($name, ['Heading', 'HeroTitle', 'FeatureTitle', 'FeaturedCTATitle', 'Title', 'ServiceName', 'CategoryName'], true) => 70,
        in_array($name, ['Eyebrow', 'HeroEyebrow'], true)      => 80,
        in_array($name, ['ShortDescription', 'Excerpt', 'FeatureSummary', 'Lead'], true) => 200,
        str_ends_with($name, 'Alt') || $name === 'AltText'     => 125,
        default                                                => null,
    };
}

/** Why the recommended length matters, shown when it's exceeded. */
function cms_ideal_reason(string $name): string
{
    return match (true) {
        in_array($name, ['MetaTitle', 'MetaDescription'], true) => 'Search engines usually cut off anything longer.',
        in_array($name, ['Subject', 'PreviewText'], true)       => 'Email apps usually cut off anything longer.',
        str_ends_with($name, 'Alt') || $name === 'AltText'      => 'Screen readers read it aloud, so short and clear is best.',
        (bool) preg_match('/(ButtonText|^CTA\dText|ReadMoreText)$/', $name) => 'Longer button text may wrap onto two lines.',
        default                                                 => 'Longer text may crowd the design. Check how it looks.',
    };
}

/**
 * A text box for formatted articles (see includes/rich-text.php): a toolbar
 * that inserts the formatting marks, a "how it looks" preview, and a short
 * guide. Without JavaScript it is an ordinary text box.
 */
function cms_rich_editor(string $attrs, string $value, int $rows = 18): string
{
    $buttons = [
        ['bold', 'Bold', '<strong>B</strong>'],
        ['italic', 'Italic', '<em>I</em>'],
        ['link', 'Link', 'Link'],
        ['heading', 'Heading', 'Heading'],
        ['list', 'Bullet list', 'List'],
        ['quote', 'Pull quote', 'Quote'],
        ['image', 'Add a picture: upload one, or choose from the Media Library', 'Picture'],
        ['section', 'Highlighted section', 'Section'],
        ['button', 'Button', 'Button'],
        ['more', 'Newsletter "read more" point', 'Read more'],
    ];
    $toolbar = '<div class="rich-toolbar" role="toolbar" aria-label="Formatting" hidden>';
    foreach ($buttons as [$action, $label, $text]) {
        $toolbar .= '<button type="button" class="rich-toolbar__button" data-rich="' . $action . '" title="' . e($label) . '" aria-label="' . e($label) . '">' . $text . '</button>';
    }
    $toolbar .= '<button type="button" class="rich-toolbar__button rich-toolbar__preview" data-rich="preview" aria-pressed="false">Preview</button></div>';

    $guide = '<details class="rich-guide"><summary>Formatting guide</summary><dl>'
           . '<div><dt>Paragraphs</dt><dd>Leave a blank line between them.</dd></div>'
           . '<div><dt>**bold**, _italic_</dt><dd>Text between two stars is bold; between underscores is italic.</dd></div>'
           . '<div><dt>## Heading, ### Smaller heading</dt><dd>At the start of a line.</dd></div>'
           . '<div><dt>- item</dt><dd>A bullet point (also * or •). Use 1. for numbered points.</dd></div>'
           . '<div><dt>[words](contact.php)</dt><dd>A link to a page on this site, or a full https:// address.</dd></div>'
           . '<div><dt>&gt; quote</dt><dd>A pull quote. Add a last line &gt; — Name for who said it.</dd></div>'
           . '<div><dt>[[image …]]</dt><dd>A picture (uploaded, or from the Media Library), with an optional caption after |.</dd></div>'
           . '<div><dt>[[section green]] … [[/section]]</dt><dd>A highlighted block: peach, green, gold or white.</dd></div>'
           . '<div><dt>[[button Words | page]]</dt><dd>A button, for example [[button Book a consultation | book-consultation.php]].</dd></div>'
           . '<div><dt>[[more]]</dt><dd>Newsletters show the article up to this point, then a Read more button.</dd></div>'
           . '</dl></details>';

    return '<div class="rich-editor" data-rich-editor data-base="' . e(url('')) . '" data-preview-url="' . e(url('admin/rich-preview.php')) . '">' . $toolbar
         . '<textarea rows="' . $rows . '" ' . $attrs . '>' . e($value) . '</textarea>'
         . '<div class="rich-preview content-document rich-text" hidden aria-live="polite"></div>' . $guide . '</div>';
}

/** Drop zone, preview and library button. The path is held in a hidden field. */
function cms_render_image_control(string $name, array $field, string $value, string $id, string $help, string $message): string
{
    $targets = ' data-alt-target="field-' . e($field['alt_field'] ?? '') . '"'
             . (empty($field['caption_field']) ? '' : ' data-caption-target="field-' . e($field['caption_field']) . '"');
    $hidden  = $value === '' ? ' hidden' : '';

    return '<div class="admin-field" id="row-' . e($name) . '"><span class="admin-field__label" id="' . e($id) . '-label">' . e($field['label']) . '</span>'
         . '<div class="media-field" data-media-field data-category="' . e($GLOBALS['cms_form_title'] ?? '') . '"' . $targets . '>'
         . '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '" data-media-value>'
         . '<div class="media-field__preview"' . $hidden . '><img src="' . ($value === '' ? '' : e(image_url($value, 'thumb'))) . '" alt=""></div>'
         . '<div class="media-field__controls"><div class="media-drop"><label>'
         . '<span class="media-drop__text">Drag an image here, or <span class="media-drop__link">browse</span></span>'
         . '<span class="media-drop__hint">JPG, PNG, WebP or GIF, up to ' . (int) (MEDIA_MAX_BYTES / 1048576) . ' MB</span>'
         . '<input class="visually-hidden" type="file" accept="image/jpeg,image/png,image/webp,image/gif" aria-labelledby="' . e($id) . '-label"></label></div>'
         . '<div class="media-field__buttons"><button class="btn btn--outline btn--small" type="button" data-media-library>Choose from library</button>'
         . '<button class="admin-delete__button" type="button" data-media-remove' . $hidden . '>Remove image</button></div></div>'
         . '<p class="media-field__status" role="status"></p></div>'
         . '<noscript><p class="admin-error">Images need JavaScript to be switched on.</p></noscript>'
         . $help . $message . '</div>';
}

function cms_render_form(array $module, array $row, array $errors, string $cancelUrl, bool $posted): string
{
    $GLOBALS['cms_form_title'] = $module['title'];
    $html = '<form method="post" class="admin-form" novalidate>' . csrf_field();

    if ($errors !== []) {
        $html .= '<div class="admin-alert admin-alert--error" role="alert">'
               . e($errors['_form'] ?? 'Please correct the highlighted fields.') . '</div>';
    }

    $restricted = false;
    foreach (cms_group_fields($module['fields']) as $group => $fields) {
        $controls = '';
        $problem  = false;
        foreach ($fields as $name => $field) {
            if (!cms_field_editable($field)) {
                $restricted = true;
                continue;
            }
            if (($field['form'] ?? true) === false) {
                continue; // set by the screen's own buttons, for example Publish now
            }
            $problem = $problem || isset($errors[$name]);
            if ($posted) {
                $value = $field['type'] === 'checkbox' ? (isset($_POST[$name]) ? '1' : '') : (is_string($_POST[$name] ?? null) ? $_POST[$name] : '');
            } else {
                $value = (string) ($row[$name] ?? '');
            }
            $controls .= cms_render_control($name, $field, $value, $errors[$name] ?? null);
        }
        if ($controls !== '' && in_array($group, $module['collapsed'] ?? [], true)) {
            // Rarely needed fields, closed until opened (or until one needs correcting).
            $html .= '<details class="admin-panel admin-more" id="group-' . e(preg_replace('/[^a-z0-9]+/', '-', strtolower($group))) . '"' . ($problem ? ' open' : '') . '>'
                   . '<summary class="admin-panel__title">' . e($group) . '</summary>'
                   . (isset($module['collapsed_help']) ? '<p class="admin-help">' . e($module['collapsed_help']) . '</p>' : '') . $controls . '</details>';
        } elseif ($controls !== '') {
            $html .= '<fieldset class="admin-panel"><legend class="admin-panel__title">' . e($group) . '</legend>' . $controls . '</fieldset>';
        }
    }

    if ($restricted) {
        $html .= '<p class="admin-help">Publishing status and web addresses can only be changed by an Admin.</p>';
    }

    $html .= cms_seo_panel($module, $posted ? array_merge($row, array_filter($_POST, 'is_string')) : $row);

    return $html . '<div class="admin-actions"><button class="btn btn--primary" type="submit">Save changes</button>'
                 . '<a class="btn btn--outline" href="' . e($cancelUrl) . '">Cancel</a></div></form>';
}

/**
 * Complete page for a single-row section: read-only view, or the form with
 * ?edit=1. Single-row sections have no create or delete.
 */
function cms_singleton_page(string $key): void
{
    $module  = cms_module($key);
    $editing = isset($_GET['edit']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

    admin_guard($module['permission'] . ($editing ? '.edit' : '.view'));

    $row = cms_load($module, 1);
    if ($row === null) {
        admin_stop(500, 'Section not set up', 'Run the database setup to create this section.');
    }

    $page   = url($module['page']);
    $posted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $errors = $posted ? cms_handle_post($module, 1, $row, $page) : [];

    $pageTitle = $module['title'];
    $layout    = 'admin';
    require INCLUDES_PATH . '/header.php';

    echo '<main id="main-content" class="admin-content">', cms_flash();
    echo '<div class="admin-toolbar">', cms_last_edited($row), cms_history_link($module, 1);
    if (!$editing && can($module['permission'] . '.edit')) {
        echo '<a class="btn btn--primary btn--small" href="', e($page), '?edit=1">Edit</a>';
    }
    echo '</div>';
    echo $editing ? cms_render_form($module, $row, $errors, $page, $posted) : cms_render_view($module, $row);
    echo '</main>';

    require INCLUDES_PATH . '/footer.php';
}

function admin_empty_state(string $title, string $message, string $icon = 'leaf'): string
{
    return '<div class="admin-empty"><span class="admin-empty__icon">' . icon($icon) . '</span>'
         . '<h2 class="admin-empty__title">' . e($title) . '</h2><p>' . e($message) . '</p></div>';
}

/** Guarded placeholder for a module whose screens arrive in a later phase. */
function cms_placeholder_page(string $permission, string $title, string $table, string $noun, string $icon): void
{
    admin_guard($permission);

    $pageTitle = $title;
    $layout    = 'admin';
    $count     = (int) db_value("SELECT COUNT(*) FROM `$table`");

    require INCLUDES_PATH . '/header.php';
    echo '<main id="main-content" class="admin-content">',
         '<p class="admin-meta">', $count, ' ', e($noun), ' in the database</p>',
         admin_empty_state($title . ' is coming soon', 'This screen will be built in a later phase.', $icon),
         '</main>';
    require INCLUDES_PATH . '/footer.php';
}

// ---------------------------------------------------------------------------
// "How it may look in Google": a live preview of the search result, and a
// short checklist in plain words, on the edit screens of blog posts,
// services, projects, the home and About pages and each page's search
// engine section. Filled in as you type by admin.js; this says which fields
// to read.
// ---------------------------------------------------------------------------

function cms_seo_config(array $module, array $row): ?array
{
    require_once INCLUDES_PATH . '/content.php';
    $config = $module['seo'] ?? match ($module['table']) {
        'BlogPosts'         => ['title' => ['MetaTitle', 'Title'], 'description' => ['MetaDescription', 'Excerpt'], 'path' => 'blog/blog-post.php?slug={Slug}', 'slugFrom' => 'Title', 'content' => 'Content', 'image' => 'FeaturedImage', 'alt' => 'FeaturedImageAlt', 'category' => 'CategoryID', 'minWords' => 300, 'heading' => true, 'link' => true],
        'Services'          => ['title' => ['MetaTitle', 'ServiceName'], 'description' => ['MetaDescription', 'ShortDescription', 'Lead'], 'path' => 'services/{Slug}.php', 'content' => 'FullDescription', 'image' => 'FeaturedImage', 'alt' => 'FeaturedImageAlt', 'minWords' => 150],
        'PortfolioProjects' => ['title' => ['MetaTitle', 'Title'], 'description' => ['MetaDescription', 'Description'], 'path' => 'portfolio/project.php?slug={Slug}', 'slugFrom' => 'Title', 'content' => 'Description', 'image' => 'FeaturedImage', 'alt' => 'FeaturedImageAlt', 'minWords' => 80],
        'HomePageContent'   => ['title' => ['MetaTitle'], 'description' => ['MetaDescription'], 'path' => '', 'image' => 'HeroImage', 'alt' => 'HeroImageAlt', 'home' => true],
        'AboutContent'      => ['title' => ['MetaTitle'], 'description' => ['MetaDescription'], 'path' => 'about.php', 'content' => 'Biography', 'image' => 'PortraitImage', 'alt' => 'PortraitImageAlt', 'minWords' => 150, 'fallbackTitle' => (string) (site_sections('about')['page-header']['Heading'] ?? 'About')],
        default             => null,
    };
    if ($config === null) {
        return null;
    }
    $fields = array_merge($config['title'], $config['description'], array_filter([$config['content'] ?? null, $config['image'] ?? null, $config['alt'] ?? null, $config['category'] ?? null, 'Slug', $config['slugFrom'] ?? null]));
    $values = [];
    foreach (array_unique($fields) as $field) {
        $values[$field] = (string) ($row[$field] ?? '');
    }
    return $config + [
        'site'        => absolute_url(''),
        'brand'       => SITE_NAME,
        'tagline'     => site_tagline() ?: SITE_TAGLINE,
        'values'      => $values, // for fields this person can't edit, so aren't on the form
    ];
}

function cms_seo_panel(array $module, array $row): string
{
    $config = cms_seo_config($module, $row);
    if ($config === null) {
        return '';
    }
    return '<section class="admin-panel seo-check" data-seo="' . e(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '" aria-labelledby="seo-check-title">'
         . '<h2 class="admin-panel__title" id="seo-check-title">How it may look in Google</h2>'
         . '<div class="seo-check__preview" aria-hidden="true"><p class="seo-check__site"></p><p class="seo-check__title"></p><p class="seo-check__description"></p></div>'
         . '<h3 class="seo-check__subtitle">Checklist</h3><ul class="seo-check__list" aria-live="polite"></ul>'
         . '<p class="admin-help">A guide, not rules: nothing here stops you saving. Search results change the wording slightly as they see fit.</p>'
         . '</section>';
}
