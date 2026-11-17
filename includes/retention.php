<?php
/**
 * How long personal details are kept (POPIA: keep personal information no
 * longer than needed). Periods are set in Website Settings → Privacy; 0 means
 * keep. data_cleanup() runs at most once a day: from the scheduled task
 * (database/send-campaigns.php or scheduled-tasks.php) and, in case that
 * isn't set up, when someone opens the dashboard.
 *
 * What is removed:
 *   - sign-in attempts older than KeepLoginDays, and internet (IP) addresses
 *     on the activity log and contact messages after the same time
 *   - contact messages marked Responded or Closed, KeepMessagesMonths after
 *     their last change (New and In progress messages are never removed)
 *   - activity log entries older than KeepActivityMonths
 *   - people who unsubscribed more than KeepUnsubscribedMonths ago (with
 *     their newsletter delivery records)
 *   - used or expired password links after 30 days
 *   - visitor statistics older than KeepStatisticsMonths
 *
 * A subscriber's consent record (date and IP address) is kept while they are
 * subscribed: it is the proof that they asked for the newsletter.
 */

declare(strict_types=1);

/** Run the clean-up if it hasn't run in the last day ($force: run now). Returns what was removed, or null if skipped. */
function data_cleanup(bool $force = false): ?array
{
    try {
        $s = db_row('SELECT KeepLoginDays, KeepMessagesMonths, KeepActivityMonths, KeepUnsubscribedMonths, KeepStatisticsMonths, LastCleanupDate FROM WebsiteSettings WHERE SettingID = 1');
    } catch (Throwable $error) {
        return null; // database not updated yet
    }
    if ($s === null || (!$force && $s['LastCleanupDate'] !== null && strtotime((string) $s['LastCleanupDate']) > time() - 20 * 3600)) {
        return null;
    }
    // Claim today's run first, so two requests at once don't both do it.
    db_query('UPDATE WebsiteSettings SET LastCleanupDate = NOW() WHERE SettingID = 1');

    $done = [];
    $run  = static function (string $label, string $sql, array $params = []) use (&$done): void {
        $count = db_query($sql, $params)->rowCount();
        if ($count > 0) {
            $done[$label] = $count;
        }
    };

    if (($months = (int) $s['KeepMessagesMonths']) > 0) {
        $run('answered or closed messages', "DELETE FROM ContactMessages WHERE Status IN ('Responded', 'Closed') AND UpdatedDate < NOW() - INTERVAL ? MONTH", [$months]);
    }
    if (($months = (int) $s['KeepActivityMonths']) > 0) {
        $run('activity log entries', 'DELETE FROM ActivityLog WHERE CreatedDate < NOW() - INTERVAL ? MONTH', [$months]);
    }
    if (($months = (int) $s['KeepUnsubscribedMonths']) > 0) {
        $run('unsubscribed people', "DELETE FROM Subscribers WHERE SubscriptionStatus = 'Unsubscribed' AND UnsubscribedDate < NOW() - INTERVAL ? MONTH", [$months]);
    }
    if (($days = (int) $s['KeepLoginDays']) > 0) {
        $run('sign-in attempts', 'DELETE FROM LoginAttempts WHERE AttemptDate < NOW() - INTERVAL ? DAY', [$days]);
        $run('IP addresses on the activity log', 'UPDATE ActivityLog SET IPAddress = NULL WHERE IPAddress IS NOT NULL AND CreatedDate < NOW() - INTERVAL ? DAY', [$days]);
        // UpdatedDate is kept as it was: removing an address is not a change to the message.
        $run('IP addresses on messages', 'UPDATE ContactMessages SET IPAddress = NULL, UpdatedDate = UpdatedDate WHERE IPAddress IS NOT NULL AND CreatedDate < NOW() - INTERVAL ? DAY', [$days]);
    }
    $run('old password links', 'DELETE FROM PasswordResets WHERE (UsedDate IS NOT NULL OR ExpiryDate < NOW()) AND CreatedDate < NOW() - INTERVAL 30 DAY');
    if (($months = (int) $s['KeepStatisticsMonths']) > 0) {
        $run('daily page counts', 'DELETE FROM PageViews WHERE ViewDate < CURDATE() - INTERVAL ? MONTH', [$months]);
        $run('daily referrer counts', 'DELETE FROM PageReferrers WHERE ViewDate < CURDATE() - INTERVAL ? MONTH', [$months]);
    }

    if ($done !== []) {
        $summary = implode(', ', array_map(static fn(string $label, int $count): string => $count . ' ' . $label, array_keys($done), $done));
        activity_log('data_cleanup', null, null, 'Removed ' . $summary);
    }
    return $done;
}
