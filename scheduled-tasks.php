<?php
/**
 * Scheduled tasks for hosts that can only call a web address on a timer (or
 * a free online cron service): starts scheduled newsletters whose time has
 * come and continues any send in progress.
 *
 *   https://your-domain/scheduled-tasks.php?key=CRON_KEY
 *
 * Call it every 10 minutes. It is switched off (page not found) unless
 * CRON_KEY in .env is set to a random value of at least 32 characters, and
 * the key in the address must match. Hosts with real cron should run
 * database/send-campaigns.php instead, which can run for longer.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$expected = (string) env_value('CRON_KEY', '');
$given    = is_string($_GET['key'] ?? null) ? $_GET['key'] : '';
if (strlen($expected) < 32 || !hash_equals($expected, $given)) {
    http_response_code(404);
    exit;
}

require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

// Online cron services usually wait about 30 seconds for an answer; whatever
// isn't sent in that time continues on the next call.
ignore_user_abort(true);
set_time_limit(120);
foreach (campaign_run(25) as $line) {
    echo $line, "\n";
}

// Once a day: remove personal details past their keeping period (includes/retention.php).
require_once INCLUDES_PATH . '/retention.php';
if ($removed = data_cleanup()) {
    echo 'Clean-up: ', implode(', ', array_map(static fn($label, $count) => $count . ' ' . $label, array_keys($removed), $removed)), "\n";
}
