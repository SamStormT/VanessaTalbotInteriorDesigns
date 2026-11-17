<?php
/**
 * Two-step sign-in: after the password, a 6-digit code from an authenticator
 * app (Google Authenticator, Microsoft Authenticator, Authy, 1Password and
 * others). The standard TOTP method (RFC 6238): the app and the website share
 * a secret key, and both work out the same code from the time, every 30
 * seconds.
 *
 *   - A code is accepted for 30 seconds either side, for phone clocks that
 *     are slightly out, and never twice (the last used time step is kept).
 *   - Ten one-time backup codes are given when it is switched on, for a lost
 *     phone. Only their hashes are stored, like passwords.
 *   - A SuperAdmin can switch it off for someone who has lost their phone and
 *     their backup codes (admin/user-edit.php).
 */

declare(strict_types=1);

const TWOFACTOR_PERIOD          = 30;
const TWOFACTOR_DIGITS          = 6;
const TWOFACTOR_RECOVERY_COUNT  = 10;
const TWOFACTOR_PENDING_SECONDS = 300; // time allowed between the password and the code

/** A new random secret key, in the base-32 letters authenticator apps expect. */
function twofactor_new_secret(): string
{
    return twofactor_base32_encode(random_bytes(20));
}

function twofactor_base32_encode(string $bytes): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits     = '';
    foreach (str_split($bytes) as $byte) {
        $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $out;
}

function twofactor_base32_decode(string $text): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits     = '';
    foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text))) as $char) {
        $bits .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $out .= chr(bindec($chunk));
        }
    }
    return $out;
}

/** The code for one 30-second time step. */
function twofactor_code(string $secret, int $step): string
{
    $hash   = hash_hmac('sha1', pack('J', $step), twofactor_base32_decode($secret), true);
    $offset = ord($hash[19]) & 0x0F;
    $number = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
    return str_pad((string) ($number % (10 ** TWOFACTOR_DIGITS)), TWOFACTOR_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * Check a code. Returns the time step it matched (to store, so it can't be
 * used again), or null.
 */
function twofactor_check(string $secret, string $code, ?int $lastStep, ?int $now = null): ?int
{
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{' . TWOFACTOR_DIGITS . '}$/', $code)) {
        return null;
    }
    $current = intdiv($now ?? time(), TWOFACTOR_PERIOD);
    foreach ([0, -1, 1] as $drift) {
        $step = $current + $drift;
        if (($lastStep === null || $step > $lastStep) && hash_equals(twofactor_code($secret, $step), $code)) {
            return $step;
        }
    }
    return null;
}

/** The address authenticator apps read from the QR code. */
function twofactor_uri(string $secret, string $email): string
{
    $issuer = SITE_NAME;
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $email)
         . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer) . '&digits=' . TWOFACTOR_DIGITS . '&period=' . TWOFACTOR_PERIOD;
}

/** New one-time backup codes, as people will type them (abcd-efgh). */
function twofactor_new_recovery_codes(): array
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789'; // no 0/o, 1/l/i to misread
    $codes    = [];
    for ($i = 0; $i < TWOFACTOR_RECOVERY_COUNT; $i++) {
        $code = '';
        for ($j = 0; $j < 8; $j++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $codes[] = substr($code, 0, 4) . '-' . substr($code, 4);
    }
    return $codes;
}

/** Backup codes as stored: a JSON list of their hashes. */
function twofactor_hash_codes(array $codes): string
{
    return json_encode(array_map(static fn(string $code): string => password_hash(twofactor_normal_code($code), PASSWORD_DEFAULT), $codes));
}

function twofactor_normal_code(string $code): string
{
    return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $code));
}

/** Use a backup code: true (and it is removed) if it matches one not yet used. */
function twofactor_use_recovery_code(int $adminId, string $code): bool
{
    $code = twofactor_normal_code($code);
    if (strlen($code) !== 8) {
        return false;
    }
    $stored = json_decode((string) db_value('SELECT TwoFactorRecovery FROM AdminUsers WHERE AdminUserID = ?', [$adminId]), true) ?: [];
    foreach ($stored as $index => $hash) {
        if (password_verify($code, $hash)) {
            unset($stored[$index]);
            db_query('UPDATE AdminUsers SET TwoFactorRecovery = ? WHERE AdminUserID = ?', [json_encode(array_values($stored)), $adminId]);
            return true;
        }
    }
    return false;
}

function twofactor_codes_left(?string $stored): int
{
    return count(json_decode((string) $stored, true) ?: []);
}

/**
 * The second step of signing in. Returns 'ok', 'invalid', 'locked' or
 * 'expired' (no password step in the last few minutes).
 */
function twofactor_verify_login(string $code): string
{
    $pending = $_SESSION['twofactor'] ?? null;
    if (!is_array($pending) || time() - (int) $pending['at'] > TWOFACTOR_PENDING_SECONDS) {
        unset($_SESSION['twofactor']);
        return 'expired';
    }
    $ip = client_ip();
    if (login_is_locked((string) $pending['email'], $ip)) {
        return 'locked';
    }
    $user = db_row('SELECT AdminUserID, Email, Status, TwoFactorSecret, TwoFactorLastStep FROM AdminUsers WHERE AdminUserID = ?', [(int) $pending['id']]);
    if ($user === null || $user['Status'] !== 'Active' || empty($user['TwoFactorSecret'])) {
        unset($_SESSION['twofactor']);
        return 'expired';
    }

    $step = twofactor_check((string) $user['TwoFactorSecret'], $code, $user['TwoFactorLastStep'] === null ? null : (int) $user['TwoFactorLastStep']);
    if ($step !== null) {
        db_query('UPDATE AdminUsers SET TwoFactorLastStep = ? WHERE AdminUserID = ?', [$step, $user['AdminUserID']]);
    } elseif (twofactor_use_recovery_code((int) $user['AdminUserID'], $code)) {
        activity_log('backup_code_used', 'AdminUsers', (int) $user['AdminUserID'], twofactor_codes_left((string) db_value('SELECT TwoFactorRecovery FROM AdminUsers WHERE AdminUserID = ?', [$user['AdminUserID']])) . ' backup codes left', (int) $user['AdminUserID']);
    } else {
        login_record_attempt((string) $user['Email'], $ip, false);
        return 'invalid';
    }

    unset($_SESSION['twofactor']);
    auth_complete_login((int) $user['AdminUserID'], (string) $user['Email']);
    return 'ok';
}
