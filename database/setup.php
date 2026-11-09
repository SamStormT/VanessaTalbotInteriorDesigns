<?php
/**
 * Database setup (command line only). Safe to run again at any time.
 *
 *   php database/setup.php
 *       Applies schema.sql and migrations.sql, loads first-run Website
 *       Settings from .env, and creates the first SuperAdmin if none exists.
 *
 *   php database/setup.php --print-admin-sql
 *       Prints an INSERT for the first SuperAdmin without touching a database,
 *       for hosts with no command line: paste it into phpMyAdmin.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once INCLUDES_PATH . '/auth.php';

function ask(string $label): string
{
    fwrite(STDOUT, $label . ': ');
    return trim((string) fgets(STDIN));
}

/** @return array{0:string,1:string,2:string,3:string} name, surname, email, password hash */
function ask_for_admin(): array
{
    $name     = ask('First name');
    $surname  = ask('Surname');
    $email    = strtolower(ask('Email'));
    $password = ask('Password (' . PASSWORD_MIN_LENGTH . ' characters or more)');

    if ($name === '' || $surname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('A name, surname and valid email are required.');
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        throw new RuntimeException('That password is too short.');
    }

    return [$name, $surname, $email, password_hash($password, PASSWORD_DEFAULT)];
}

function run_sql_file(string $path): void
{
    $sql = (string) file_get_contents($path);
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

    foreach (preg_split('/;\s*\n/', $sql) ?: [] as $statement) {
        if (trim($statement) !== '') {
            db()->exec($statement);
        }
    }
    echo 'Applied ', basename($path), PHP_EOL;
}

if (in_array('--print-admin-sql', $argv, true)) {
    $values = array_map(static fn(string $v): string => "'" . str_replace(['\\', "'"], ['\\\\', "''"], $v) . "'", ask_for_admin());
    echo PHP_EOL, 'INSERT INTO AdminUsers (Name, Surname, Email, PasswordHash, Role) VALUES (', implode(', ', $values), ", 'SuperAdmin');", PHP_EOL;
    exit;
}

run_sql_file(__DIR__ . '/schema.sql');
run_sql_file(__DIR__ . '/migrations.sql');

// COALESCE keeps anything already saved in the admin area.
db_query(
    'UPDATE WebsiteSettings SET Phone = COALESCE(Phone, NULLIF(?, \'\')), Email = COALESCE(Email, NULLIF(?, \'\')) WHERE SettingID = 1',
    [env_value('SEED_PHONE'), env_value('SEED_EMAIL')]
);
echo 'Website Settings defaults checked', PHP_EOL;

if ((int) db_value("SELECT COUNT(*) FROM AdminUsers WHERE Role = 'SuperAdmin'") === 0) {
    echo PHP_EOL, 'Create the first SuperAdmin', PHP_EOL;
    [$name, $surname, $email, $hash] = ask_for_admin();
    db_query("INSERT INTO AdminUsers (Name, Surname, Email, PasswordHash, Role) VALUES (?, ?, ?, ?, 'SuperAdmin')", [$name, $surname, $email, $hash]);
    echo 'SuperAdmin created', PHP_EOL;
}

echo 'Setup complete', PHP_EOL;
