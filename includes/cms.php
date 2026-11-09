<?php
/**
 * CMS framework: turns a definition in cms-modules.php into a read-only view,
 * an edit form, validation, saving, LastEdited stamps and an activity entry.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/cms-modules.php';
require_once INCLUDES_PATH . '/cms-records.php';

function cms_module(string $key): array
{
    return cms_modules()[$key] ?? throw new InvalidArgumentException('Unknown CMS module.');
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

        $raw   = $input[$name] ?? '';
        $value = is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '';
        $label = $field['label'];

        if ($value === '') {
            if (!empty($field['required'])) {
                $errors[$name] = "$label is required.";
            }
            $data[$name] = $field['type'] === 'int' && empty($field['nullable']) ? 0 : null;
            continue;
        }

        if (isset($field['max']) && mb_strlen($value) > $field['max']) {
            $errors[$name] = "$label can be at most {$field['max']} characters.";
        }

        $problem = match ($field['type']) {
            'email'  => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email address.',
            'tel'    => preg_match('/^\+?[0-9][0-9 ()-]{5,28}$/', $value) ? null : 'Enter a valid phone number.',
            'url'    => preg_match('#^https?://[^\s<>"]+$#i', $value) && filter_var($value, FILTER_VALIDATE_URL) ? null : 'Enter a full web address starting with https://',
            'link'   => link_is_safe($value) ? null : 'Enter a page such as contact.php, or a full address starting with https://',
            'path'   => preg_match('#^[A-Za-z0-9_-][A-Za-z0-9_./ -]*$#', $value) && !str_contains($value, '..') ? null : 'Enter a file path such as uploads/home/hero.jpg',
            'slug'   => preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value) ? null : 'Use lower-case letters, numbers and single hyphens only.',
            'int'    => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : 'Enter a whole number.',
            'date'   => (DateTime::createFromFormat('!Y-m-d', $value) ?: null)?->format('Y-m-d') === $value ? null : 'Enter a valid date.',
            'select' => in_array($value, $field['options'], true) ? null : 'Choose one of the listed options.',
            default  => null,
        };
        if ($problem !== null) {
            $errors[$name] = $problem;
        }

        $data[$name] = $field['type'] === 'int' ? (int) $value : $value;
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
 * Save changes, stamp LastEditedBy/LastEditedDate and write the activity log.
 * Returns the names of the fields that changed.
 */
function cms_save(array $module, int $id, array $data, array $existing): array
{
    $changed = [];
    foreach ($data as $name => $value) {
        if ((string) ($existing[$name] ?? '') !== (string) ($value ?? '')) {
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
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }

    return array_keys($changed);
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

    if ($field['type'] === 'checkbox') {
        $checked = $value !== '' && $value !== '0' ? ' checked' : '';
        return '<div class="admin-field admin-field--check"><label><input type="checkbox" value="1" ' . $attrs . $checked . '> '
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
    } elseif (in_array($field['type'], ['textarea', 'lines'], true)) {
        $rows    = ($field['max'] ?? 0) > 5000 ? 8 : 4;
        $control = '<textarea rows="' . $rows . '" ' . $attrs . '>' . e($value) . '</textarea>';
    } else {
        $type = match ($field['type']) {
            'email' => 'email', 'tel' => 'tel', 'url' => 'url', 'int' => 'number', 'date' => 'date', default => 'text',
        };
        $max     = isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '';
        $control = '<input type="' . $type . '" value="' . e($value) . '"' . $max . ' ' . $attrs . '>';
    }

    return '<div class="admin-field">' . $label . $control . $help . $message . '</div>';
}

function cms_render_form(array $module, array $row, array $errors, string $cancelUrl, bool $posted): string
{
    $html = '<form method="post" class="admin-form" novalidate>' . csrf_field();

    if ($errors !== []) {
        $html .= '<div class="admin-alert admin-alert--error" role="alert">'
               . e($errors['_form'] ?? 'Please correct the highlighted fields.') . '</div>';
    }

    $restricted = false;
    foreach (cms_group_fields($module['fields']) as $group => $fields) {
        $controls = '';
        foreach ($fields as $name => $field) {
            if (!cms_field_editable($field)) {
                $restricted = true;
                continue;
            }
            if ($posted) {
                $value = $field['type'] === 'checkbox' ? (isset($_POST[$name]) ? '1' : '') : (is_string($_POST[$name] ?? null) ? $_POST[$name] : '');
            } else {
                $value = (string) ($row[$name] ?? '');
            }
            $controls .= cms_render_control($name, $field, $value, $errors[$name] ?? null);
        }
        if ($controls !== '') {
            $html .= '<fieldset class="admin-panel"><legend class="admin-panel__title">' . e($group) . '</legend>' . $controls . '</fieldset>';
        }
    }

    if ($restricted) {
        $html .= '<p class="admin-help">Publishing status and web addresses can only be changed by an Admin.</p>';
    }

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
    echo '<div class="admin-toolbar">', cms_last_edited($row);
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
