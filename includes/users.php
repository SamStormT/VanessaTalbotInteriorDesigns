<?php
/**
 * User management: create, edit, change role, disable, reactivate, and send a
 * password link. Only the SuperAdmin reaches these screens (permission
 * "users.*" in permissions.php).
 *
 * Rules enforced here, whatever the screen sends:
 *   - The last active SuperAdmin can't be disabled or given a lower role.
 *   - Nobody can change their own role or disable their own account.
 *   - Accounts are never deleted (their name stays on the activity log and
 *     on the content they edited); they are disabled instead.
 *
 * New users choose their own password from an emailed link, so no password
 * is ever typed, seen or sent by anyone else.
 */

declare(strict_types=1);

require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/mail.php';

/** AdminUsers.Role values, highest first, with what each can do. */
function user_roles(): array
{
    return [
        'SuperAdmin' => 'Everything, including users and the activity log',
        'Admin'      => 'All content, publishing, settings, media, messages and subscribers',
        'Editor'     => 'Prepares content; an Admin publishes it',
    ];
}

/** Rank used to compare roles: a higher number is a more powerful role. */
function user_role_rank(string $role): int
{
    return ['Editor' => 1, 'Admin' => 2, 'SuperAdmin' => 3][$role] ?? 0;
}

function user_find(int $id): ?array
{
    return db_row('SELECT AdminUserID, Name, Surname, Email, Role, Status, CreatedDate, LastLogin, PasswordChangedDate FROM AdminUsers WHERE AdminUserID = ?', [$id]);
}

/** Active SuperAdmins, locked until the transaction ends so two changes can't race past the rule. */
function user_active_superadmins_locked(): int
{
    return count(db_all("SELECT AdminUserID FROM AdminUsers WHERE Role = 'SuperAdmin' AND Status = 'Active' FOR UPDATE"));
}

/**
 * Check the user form. $existing is null when creating.
 *
 * @return array{0: array<string, string>, 1: array<string, string>}
 */
function user_validate(array $input, ?array $existing, array $actor): array
{
    $text = static fn(string $key): string => is_string($input[$key] ?? null) ? trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $input[$key])) : '';

    $data = [
        'Name'    => $text('Name'),
        'Surname' => $text('Surname'),
        'Email'   => strtolower($text('Email')),
        'Role'    => $text('Role'),
    ];
    $errors = [];

    if ($data['Name'] === '') {
        $errors['Name'] = 'First name is required.';
    } elseif (mb_strlen($data['Name']) > 100) {
        $errors['Name'] = 'Please use 100 characters or fewer.';
    }
    if ($data['Surname'] === '') {
        $errors['Surname'] = 'Surname is required.';
    } elseif (mb_strlen($data['Surname']) > 100) {
        $errors['Surname'] = 'Please use 100 characters or fewer.';
    }
    if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['Email']) > 254) {
        $errors['Email'] = 'Please enter a valid email address.';
    } elseif ((int) db_value('SELECT COUNT(*) FROM AdminUsers WHERE Email = ? AND AdminUserID <> ?', [$data['Email'], (int) ($existing['AdminUserID'] ?? 0)]) > 0) {
        $errors['Email'] = 'Another user already has this email address.';
    }

    $self = $existing !== null && (int) $existing['AdminUserID'] === (int) $actor['AdminUserID'];
    if ($self) {
        // Your own role is never taken from the form.
        $data['Role'] = $existing['Role'];
    } elseif (!array_key_exists($data['Role'], user_roles())) {
        $errors['Role'] = 'Please choose a role.';
    } elseif (user_role_rank($data['Role']) > user_role_rank($actor['Role'])) {
        $errors['Role'] = 'You cannot give anyone a role higher than your own.';
    }

    return [$data, $errors];
}

/** Create an active account and return its ID. The password is unusable until the user sets one. */
function user_create(array $data, array $actor): int
{
    db()->beginTransaction();
    try {
        db_query(
            'INSERT INTO AdminUsers (Name, Surname, Email, PasswordHash, Role, Status) VALUES (?, ?, ?, ?, ?, ?)',
            [$data['Name'], $data['Surname'], $data['Email'], password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $data['Role'], 'Active']
        );
        $id = (int) db()->lastInsertId();
        activity_log('create', 'AdminUsers', $id, 'Role: ' . $data['Role'], (int) $actor['AdminUserID']);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    return $id;
}

/**
 * Save name, email and role. Returns an error message, or null on success.
 * A role change is logged separately, with the old and new role.
 */
function user_update(array $user, array $data, array $actor): ?string
{
    $id   = (int) $user['AdminUserID'];
    $self = $id === (int) $actor['AdminUserID'];

    db()->beginTransaction();
    try {
        $current = db_row('SELECT Name, Surname, Email, Role, Status FROM AdminUsers WHERE AdminUserID = ? FOR UPDATE', [$id]);

        if ($self && $data['Role'] !== $current['Role']) {
            db()->rollBack();
            return 'You cannot change your own role.';
        }
        if ($current['Role'] === 'SuperAdmin' && $data['Role'] !== 'SuperAdmin' && $current['Status'] === 'Active'
            && user_active_superadmins_locked() <= 1) {
            db()->rollBack();
            return 'This is the only active SuperAdmin, so the role cannot be lowered. Make someone else a SuperAdmin first.';
        }

        $changed = array_keys(array_filter([
            'Name'    => $data['Name'] !== $current['Name'],
            'Surname' => $data['Surname'] !== $current['Surname'],
            'Email'   => $data['Email'] !== $current['Email'],
        ]));

        db_query('UPDATE AdminUsers SET Name = ?, Surname = ?, Email = ?, Role = ? WHERE AdminUserID = ?',
            [$data['Name'], $data['Surname'], $data['Email'], $data['Role'], $id]);

        if ($changed !== []) {
            activity_log('update', 'AdminUsers', $id, 'Changed: ' . implode(', ', $changed), (int) $actor['AdminUserID']);
        }
        if ($data['Role'] !== $current['Role']) {
            activity_log('role_change', 'AdminUsers', $id, $current['Role'] . ' to ' . $data['Role'], (int) $actor['AdminUserID']);
        }
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    return null;
}

/**
 * Disable or reactivate an account. Returns an error message, or null.
 * A disabled user is signed out on their next click and can't sign in or
 * reset their password.
 */
function user_set_status(array $user, string $status, array $actor): ?string
{
    $id = (int) $user['AdminUserID'];

    db()->beginTransaction();
    try {
        $current = db_row('SELECT Role, Status FROM AdminUsers WHERE AdminUserID = ? FOR UPDATE', [$id]);

        if ($current['Status'] === $status) {
            db()->rollBack();
            return null;
        }
        if ($status === 'Inactive') {
            if ($id === (int) $actor['AdminUserID']) {
                db()->rollBack();
                return 'You cannot disable your own account.';
            }
            if ($current['Role'] === 'SuperAdmin' && user_active_superadmins_locked() <= 1) {
                db()->rollBack();
                return 'This is the only active SuperAdmin, so the account cannot be disabled.';
            }
        }

        db_query('UPDATE AdminUsers SET Status = ? WHERE AdminUserID = ?', [$status, $id]);
        if ($status === 'Inactive') {
            // Unused reset and invitation links stop working too.
            db_query('UPDATE PasswordResets SET UsedDate = NOW() WHERE AdminUserID = ? AND UsedDate IS NULL', [$id]);
        }
        activity_log($status === 'Inactive' ? 'disable' : 'reactivate', 'AdminUsers', $id, null, (int) $actor['AdminUserID']);
        db()->commit();
    } catch (Throwable $error) {
        db()->rollBack();
        throw $error;
    }
    return null;
}

/**
 * Email a link to choose a password: an invitation for a new account, or a
 * reset for an existing one. Any earlier unused link stops working.
 * Returns ['sent' => bool, 'link' => string]. The link is returned so the
 * screen can show it once if the email could not be sent.
 */
function user_send_password_link(array $user, bool $invite, array $actor): array
{
    $minutes = $invite ? ACCOUNT_INVITE_MINUTES : PASSWORD_RESET_MINUTES;
    $token   = password_reset_create((int) $user['AdminUserID'], $minutes);
    $link    = absolute_url('reset-password.php?token=' . $token);

    $sent = mail_send($user['Email'], $invite ? 'account-invite' : 'password-reset', [
        'name'     => $user['Name'],
        'resetUrl' => $link,
        'minutes'  => $minutes,
        'role'     => $user['Role'],
        'invitedBy' => trim($actor['Name'] . ' ' . $actor['Surname']),
        'loginUrl' => absolute_url('login.php'),
    ], ['to_name' => trim($user['Name'] . ' ' . $user['Surname'])]);

    activity_log($invite ? 'invite_sent' : 'password_reset_sent', 'AdminUsers', (int) $user['AdminUserID'], $sent ? null : 'Email failed; link shown on screen', (int) $actor['AdminUserID']);

    return ['sent' => $sent, 'link' => $link];
}
