<?php
/**
 * Users: add a user, or manage one (details, role, disable or reactivate,
 * password link).
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/users.php';

$creating = !isset($_GET['id']);
$posted   = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$do       = $posted && is_string($_POST['do'] ?? null) ? $_POST['do'] : 'save';

admin_guard(match (true) {
    $creating                                   => 'users.create',
    in_array($do, ['disable', 'reactivate'], true) => 'users.archive',
    $posted                                     => 'users.edit',
    default                                     => 'users.view',
});

$actor = current_admin();
$user  = null;
if (!$creating) {
    $id   = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $user = $id > 0 ? user_find($id) : null;
    if ($user === null) {
        admin_stop(404, 'User not found', 'That user does not exist.');
    }
}

$list   = url('admin/admin-users.php');
$self   = $user !== null && (int) $user['AdminUserID'] === (int) $actor['AdminUserID'];
$errors = [];

/** Show a link once when its email could not be sent. */
$linkResult = static function (array $result, string $who, bool $invite): void {
    if ($result['sent']) {
        flash_set('success', ($invite ? 'Account created. ' : '') . 'An email with a link to choose a password has been sent to ' . $who . '.');
        return;
    }
    $_SESSION['user_link'] = $result['link'];
    flash_set('error', ($invite ? 'Account created, but the' : 'The') . ' email could not be sent. Give ' . $who . ' the link below privately; it works once.');
};

if ($posted) {
    if ($creating) {
        [$data, $errors] = user_validate($_POST, null, $actor);
        if ($errors === []) {
            try {
                $newId = user_create($data, $actor);
                $linkResult(user_send_password_link(user_find($newId), true, $actor), $data['Email'], true);
                header('Location: ' . url('admin/user-edit.php?id=' . $newId));
                exit;
            } catch (PDOException $error) {
                if (($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }
                $errors['Email'] = 'Another user already has this email address.';
            }
        }
    } elseif ($do === 'save') {
        [$data, $errors] = user_validate($_POST, $user, $actor);
        if ($errors === []) {
            try {
                $problem = user_update($user, $data, $actor);
                flash_set($problem === null ? 'success' : 'error', $problem ?? 'Saved.');
                header('Location: ' . url('admin/user-edit.php?id=' . $user['AdminUserID']));
                exit;
            } catch (PDOException $error) {
                if (($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }
                $errors['Email'] = 'Another user already has this email address.';
            }
        }
    } else {
        if ($do === 'disable' || $do === 'reactivate') {
            $problem = user_set_status($user, $do === 'disable' ? 'Inactive' : 'Active', $actor);
            flash_set($problem === null ? 'success' : 'error', $problem ?? ($do === 'disable'
                ? 'The account is disabled. They are signed out and cannot sign in.'
                : 'The account is active again. They can sign in with their existing password, or you can send a password link.'));
        } elseif ($do === 'password') {
            if ($user['Status'] !== 'Active') {
                flash_set('error', 'Reactivate the account before sending a password link.');
            } else {
                $linkResult(user_send_password_link($user, false, $actor), $user['Email'], false);
            }
        }
        header('Location: ' . url('admin/user-edit.php?id=' . $user['AdminUserID']));
        exit;
    }
}

$shownLink = $_SESSION['user_link'] ?? null;
unset($_SESSION['user_link']);

$values = $posted
    ? array_map(static fn($v) => is_string($v) ? $v : '', array_intersect_key($_POST, array_flip(['Name', 'Surname', 'Email', 'Role'])))
    : ($user ?? ['Role' => 'Editor']);

$fields = [
    'Name'    => ['label' => 'First name', 'type' => 'text', 'required' => true, 'max' => 100],
    'Surname' => ['label' => 'Surname', 'type' => 'text', 'required' => true, 'max' => 100],
    'Email'   => ['label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 254, 'help' => 'They sign in with this address, and password links are sent to it.'],
];
$roleLabels = [];
foreach (user_roles() as $role => $what) {
    $roleLabels[$role] = $role . ': ' . $what;
}
$roleField = ['label' => 'Role', 'type' => 'select', 'required' => true, 'options' => array_keys($roleLabels), 'option_labels' => $roleLabels];

$lastSuperAdmin = $user !== null && $user['Role'] === 'SuperAdmin' && $user['Status'] === 'Active'
    && (int) db_value("SELECT COUNT(*) FROM AdminUsers WHERE Role = 'SuperAdmin' AND Status = 'Active'") <= 1;

$pageTitle = $creating ? 'Add user' : trim($user['Name'] . ' ' . $user['Surname']);
$layout    = 'admin';
$activeNav = 'admin/admin-users.php';

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content">
    <?= cms_flash() ?>
<?php if ($shownLink !== null): ?>
    <div class="admin-panel">
        <label class="admin-field__label" for="password-link">Password link (shown once)</label>
        <input id="password-link" class="admin-copy" type="text" readonly value="<?= e($shownLink) ?>">
        <p class="admin-help">Send this privately, for example by WhatsApp to their phone. Anyone with the link can set the password.</p>
    </div>
<?php endif; ?>
    <div class="admin-toolbar">
        <a class="btn btn--outline btn--small" href="<?= e($list) ?>">All users</a>
    </div>

<?php if (!$creating): ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Account</h2>
        <dl class="admin-fields">
            <div><dt>Status</dt><dd><span class="admin-badge admin-badge--<?= $user['Status'] === 'Active' ? 'subscribed' : 'closed' ?>"><?= $user['Status'] === 'Active' ? 'Active' : 'Disabled' ?></span></dd></div>
            <div><dt>Created</dt><dd><?= e(cms_format_date($user['CreatedDate'])) ?></dd></div>
            <div><dt>Last signed in</dt><dd><?= $user['LastLogin'] ? e(cms_format_date($user['LastLogin'])) : '<span class="admin-muted">Not yet</span>' ?></dd></div>
            <div><dt>Password last changed</dt><dd><?= $user['PasswordChangedDate'] ? e(cms_format_date($user['PasswordChangedDate'])) : '<span class="admin-muted">Not changed since the account was set up</span>' ?></dd></div>
        </dl>
    </section>
<?php endif; ?>

<?php if ($creating || can('users.edit')): ?>
    <form method="post" class="admin-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="save">
<?php if ($errors !== []): ?>
        <div class="admin-alert admin-alert--error" role="alert">Please correct the highlighted fields.</div>
<?php endif; ?>
        <fieldset class="admin-panel">
            <legend class="admin-panel__title">Details</legend>
<?php foreach ($fields as $name => $field): ?>
            <?= cms_render_control($name, $field, (string) ($values[$name] ?? ''), $errors[$name] ?? null) ?>
<?php endforeach; ?>
<?php if ($self): ?>
            <div class="admin-field"><span class="admin-field__label">Role</span><p><?= e($user['Role']) ?></p><p class="admin-help">You cannot change your own role. Another SuperAdmin can.</p></div>
<?php else: ?>
            <?= cms_render_control('Role', $roleField, (string) ($values['Role'] ?? ''), $errors['Role'] ?? null) ?>
<?php if ($lastSuperAdmin): ?>
            <p class="admin-help">This is the only active SuperAdmin, so the role can't be lowered until someone else is made a SuperAdmin.</p>
<?php endif; ?>
<?php endif; ?>
        </fieldset>
<?php if ($creating): ?>
        <p class="admin-help">They will be emailed a link to choose their own password. The link works for 3 days.</p>
<?php endif; ?>
        <div class="admin-actions">
            <button class="btn btn--primary" type="submit"><?= $creating ? 'Create user and send invitation' : 'Save changes' ?></button>
            <a class="btn btn--outline" href="<?= e($list) ?>">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php if (!$creating): ?>
    <section class="admin-panel">
        <h2 class="admin-panel__title">Access</h2>
<?php if (can('users.edit') && $user['Status'] === 'Active'): ?>
        <form method="post" class="admin-inline-action" data-confirm="Email <?= e($user['Email']) ?> a link to choose a new password? Any earlier link stops working.">
            <?= csrf_field() ?><input type="hidden" name="do" value="password">
            <button class="btn btn--outline btn--small" type="submit">Send password reset link</button>
            <span class="admin-help">Their current password keeps working until they choose a new one. The link expires in <?= PASSWORD_RESET_MINUTES ?> minutes.</span>
        </form>
<?php endif; ?>
<?php if (can('users.archive')): ?>
<?php if ($user['Status'] === 'Active'): ?>
<?php if ($self): ?>
        <p class="admin-help">You cannot disable your own account.</p>
<?php elseif ($lastSuperAdmin): ?>
        <p class="admin-help">This is the only active SuperAdmin, so the account cannot be disabled.</p>
<?php else: ?>
        <form method="post" class="admin-inline-action" data-confirm="Disable <?= e(trim($user['Name'] . ' ' . $user['Surname'])) ?>? They will be signed out straight away and cannot sign in until the account is reactivated.">
            <?= csrf_field() ?><input type="hidden" name="do" value="disable">
            <button class="admin-delete__button" type="submit">Disable account</button>
            <span class="admin-help">Their name stays on the content they edited and in the activity log.</span>
        </form>
<?php endif; ?>
<?php else: ?>
        <form method="post" class="admin-inline-action">
            <?= csrf_field() ?><input type="hidden" name="do" value="reactivate">
            <button class="btn btn--primary btn--small" type="submit">Reactivate account</button>
        </form>
<?php endif; ?>
<?php endif; ?>
    </section>
<?php endif; ?>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
