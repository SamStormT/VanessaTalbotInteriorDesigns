<?php
/**
 * Command line: start scheduled campaigns whose time has come, and carry on
 * sending any campaign that is Sending.
 *
 *   php database/send-campaigns.php
 *
 * If the host allows scheduled tasks (cron), run this every 10 minutes:
 * scheduled newsletters then go out on time, and sends finish without a
 * browser left open. It stops at the hourly limit (NEWSLETTER_HOURLY_LIMIT
 * in .env) and carries on next time. Safe to run at any time: it never
 * emails anyone twice.
 *
 * Hosts without cron can call scheduled-tasks.php instead (see CRON_KEY).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once dirname(__DIR__) . '/includes/config.php';
require_once INCLUDES_PATH . '/cms.php';
require_once INCLUDES_PATH . '/campaigns.php';

// Up to nine minutes, so a run every ten minutes never overlaps the next.
foreach (campaign_run(540) as $line) {
    echo $line, "\n";
}
