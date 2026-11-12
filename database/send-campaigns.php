<?php
/**
 * Command line: carry on sending any campaign that is Sending.
 *
 *   php database/send-campaigns.php
 *
 * Campaigns are normally sent from the admin page, which must stay open.
 * If the host allows scheduled tasks (cron), running this every 10 to 15
 * minutes finishes sends without a browser. It stops at the hourly limit
 * (NEWSLETTER_HOURLY_LIMIT in .env) and carries on next time. Safe to run
 * at any time: it never emails anyone twice.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once dirname(__DIR__) . '/includes/config.php';
require_once INCLUDES_PATH . '/campaigns.php';

$campaigns = db_all("SELECT CampaignID, CampaignName FROM NewsletterCampaigns WHERE Status = 'Sending' ORDER BY SendStartedDate");
if ($campaigns === []) {
    echo "Nothing to send.\n";
    exit;
}

foreach ($campaigns as $campaign) {
    echo 'Sending "' . $campaign['CampaignName'] . "\"\n";
    do {
        $result = campaign_process((int) $campaign['CampaignID']);
        echo sprintf("  %d sent, %d failed, %d skipped, %d waiting\n", $result['sent'], $result['failed'], $result['skipped'], $result['pending'] + $result['sending']);
        if ($result['paused']) {
            echo '  ' . $result['message'] . "\n";
            exit;
        }
        if (!$result['done']) {
            sleep(1);
        }
    } while (!$result['done'] && $result['pending'] > 0);
}
