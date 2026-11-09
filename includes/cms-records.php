<?php
/**
 * CMS framework: modules with many records (list, create, edit, delete).
 * Driven by the 'list', 'order', 'list_page', 'edit_page' and 'label' keys of
 * a definition in cms-modules.php.
 */

declare(strict_types=1);

/** Insert a record, stamp who created it and write the activity log. Returns the new ID. */
function cms_create(array $module, array $data): int
{
    $columns = array_keys($data);
    $list    = implode(', ', array_map(static fn(string $name): string => "`$name`", $columns));
    $marks   = implode(', ', array_fill(0, count($columns), '?'));
    $admin   = current_admin();

    db()->beginTransaction();
    try {
        db_query(
            "INSERT INTO `{$module['table']}` ($list, LastEditedBy, LastEditedDate) VALUES ($marks, ?, NOW())",
            [...array_values($data), $admin['AdminUserID']]
        );
        $id = (int) db()->lastInsertId();
        activity_log('create', $module['table'], $id);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }

    return $id;
}

function cms_delete(array $module, int $id): void
{
    db()->beginTransaction();
    try {
        db_query("DELETE FROM `{$module['table']}` WHERE `{$module['key']}` = ?", [$id]);
        activity_log('delete', $module['table'], $id);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
}

/**
 * Validate and create from a POST, then redirect. $extra holds columns the
 * form doesn't show, such as a parent ID. Returns errors on failure.
 */
function cms_handle_create(array $module, string $redirect, array $extra = []): array
{
    [$data, $errors] = cms_validate($module, $_POST);

    if ($errors === []) {
        try {
            cms_create($module, $data + $extra);
            media_learn_alt_text($module, $data);
            flash_set('success', 'Added.');
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

function cms_delete_form(string $name): string
{
    return '<form method="post" class="admin-delete" data-confirm="Permanently delete ' . e($name) . '? This cannot be undone.">'
         . csrf_field() . '<input type="hidden" name="_delete" value="1">'
         . '<button class="admin-delete__button" type="submit">Delete permanently</button></form>';
}

function cms_list_cell(array $field, mixed $value): string
{
    $text = (string) ($value ?? '');
    if ($text === '') {
        return '<span class="admin-muted">Not set</span>';
    }
    if ($field['type'] === 'select' && in_array($text, ['Draft', 'Published', 'Archived'], true)) {
        return '<span class="admin-badge admin-badge--' . e(strtolower($text)) . '">' . e($text) . '</span>';
    }
    if (isset($field['option_labels'][$text])) {
        return e($field['option_labels'][$text]);
    }
    if ($field['type'] === 'date') {
        return e(date('j M Y', strtotime($text)));
    }
    return e(mb_strimwidth($text, 0, 90, '…'));
}

/** List screen for a module with many records. */
function cms_record_list_page(string $key): void
{
    $module = cms_module($key);
    admin_guard($module['permission'] . '.view');

    $rows = db_all(
        "SELECT t.*, CONCAT(u.Name, ' ', u.Surname) AS LastEditorName
         FROM `{$module['table']}` t LEFT JOIN AdminUsers u ON u.AdminUserID = t.LastEditedBy
         ORDER BY {$module['order']}"
    );

    $pageTitle = $module['title'];
    $layout    = 'admin';
    require INCLUDES_PATH . '/header.php';

    echo '<main id="main-content" class="admin-content">', cms_flash();

    if (can($module['permission'] . '.create')) {
        echo '<div class="admin-toolbar admin-toolbar--end"><a class="btn btn--primary btn--small" href="', e(url($module['edit_page'])), '">Add</a></div>';
    }

    if ($rows === []) {
        echo admin_empty_state('Nothing here yet', $module['empty'] ?? 'Records will appear here once they are added.', 'pen');
    } else {
        echo '<div class="admin-table-wrap"><table class="admin-table"><thead><tr>';
        foreach ($module['list'] as $name) {
            echo '<th scope="col">', e($module['fields'][$name]['label']), '</th>';
        }
        echo '<th scope="col">Last edited</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($module['list'] as $name) {
                echo '<td>', cms_list_cell($module['fields'][$name], $row[$name] ?? null), '</td>';
            }
            echo '<td>', cms_last_edited_cell($row), '</td><td class="admin-table__actions">';
            if (can($module['permission'] . '.edit')) {
                echo '<a class="btn btn--outline btn--small" href="', e(url($module['edit_page'] . '?id=' . (int) $row[$module['key']])), '">Edit<span class="visually-hidden"> ', e((string) $row[$module['label']]), '</span></a>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    echo '</main>';
    require INCLUDES_PATH . '/footer.php';
}

function cms_last_edited_cell(array $row): string
{
    if (empty($row['LastEditedDate'])) {
        return '<span class="admin-muted">Not edited yet</span>';
    }
    return e(trim((string) ($row['LastEditorName'] ?? '')) ?: 'A former user')
         . '<br><span class="admin-muted">' . e(cms_format_date($row['LastEditedDate'])) . '</span>';
}

/** Create and edit screen for a module with many records. */
function cms_record_edit_page(string $key, ?callable $below = null): void
{
    $module   = cms_module($key);
    $id       = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $creating = !isset($_GET['id']);
    $posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $deleting = $posted && isset($_POST['_delete']);

    admin_guard($module['permission'] . ($deleting ? '.delete' : ($creating ? '.create' : '.edit')));

    $row = $creating ? [] : cms_load($module, $id);
    if ($row === null) {
        admin_stop(404, 'Not found', 'That record does not exist.');
    }

    $list = url($module['list_page']);

    if ($deleting && !$creating) {
        cms_delete($module, $id);
        flash_set('success', 'Deleted.');
        header('Location: ' . $list);
        exit;
    }

    $errors = [];
    if ($posted) {
        $errors = $creating ? cms_handle_create($module, $list) : cms_handle_post($module, $id, $row, $list);
    }

    $pageTitle = $creating ? 'Add: ' . $module['title'] : 'Edit: ' . $row[$module['label']];
    $layout    = 'admin';
    $activeNav = $module['list_page'];
    require INCLUDES_PATH . '/header.php';

    echo '<main id="main-content" class="admin-content">';
    if (!$creating) {
        echo '<div class="admin-toolbar">', cms_last_edited($row), '</div>';
    }
    echo cms_render_form($module, $row, $errors, $list, $posted);
    if (!$creating && can($module['permission'] . '.delete')) {
        echo cms_delete_form((string) $row[$module['label']]);
    }
    if (!$creating && $below !== null) {
        $below($row);
    }
    echo '</main>';
    require INCLUDES_PATH . '/footer.php';
}
