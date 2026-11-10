<?php
/**
 * Newsletter: sign-up (POST from the home page form), and the confirm and
 * unsubscribe links from emails.
 *
 * Links from emails open a page with a button, and only the button (a POST)
 * changes anything, so email security scanners that open links can't
 * confirm or unsubscribe someone by themselves.
 *
 * Sign-ups answer in JSON when the form is sent by JavaScript, and otherwise
 * return to the home page with a message.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/newsletter.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'subscribe';

// ---------------------------------------------------------------------------
// Sign-up
// ---------------------------------------------------------------------------
if ($method === 'POST' && $action === 'subscribe') {
    $email  = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $name   = is_string($_POST['name'] ?? null) ? trim((string) preg_replace('/[\p{Cc}\p{Cf}]+/u', ' ', $_POST['name'])) : '';
    $result = 'thanks';

    if (!empty($_POST['website'])) {
        // Hidden field that only bots fill in: answer as if it worked.
        $result = 'thanks';
    } elseif ($email === '' || mb_strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($name) > 100) {
        $result = 'invalid';
    } else {
        try {
            if (newsletter_rate_limited(client_ip())) {
                $result = 'busy';
            } else {
                newsletter_subscribe($email, $name, 'Homepage Section');
            }
        } catch (Throwable $error) {
            error_log('Newsletter sign-up failed: ' . $error->getMessage());
            $result = 'error';
        }
    }

    if (str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
        http_response_code(match ($result) { 'thanks' => 200, 'invalid' => 422, 'busy' => 429, default => 500 });
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $result === 'thanks', 'message' => newsletter_messages()[$result]]);
        exit;
    }

    header('Location: ' . url('index.php?newsletter=' . $result . '#newsletter'), true, 303);
    exit;
}

// ---------------------------------------------------------------------------
// Confirm and unsubscribe links
// ---------------------------------------------------------------------------
$kind  = isset($_GET['unsubscribe']) || $action === 'unsubscribe' ? 'unsubscribe' : 'confirm';
$token = $_POST['token'] ?? $_GET[$kind] ?? '';
$token = is_string($token) ? $token : '';

if (!in_array($action, ['subscribe', 'confirm', 'unsubscribe'], true) || ($method === 'GET' && !isset($_GET['confirm']) && !isset($_GET['unsubscribe']))) {
    header('Location: ' . url('index.php#newsletter'));
    exit;
}

$title   = $kind === 'confirm' ? 'Confirm your subscription' : 'Unsubscribe';
$message = '';
$button  = null;
$state   = 'error';

try {
    if ($method === 'POST') {
        $result = $kind === 'confirm' ? newsletter_confirm($token) : newsletter_unsubscribe($token);
        [$state, $title, $message] = match ($kind . ':' . $result) {
            'confirm:confirmed', 'confirm:already' => ['success', 'You are subscribed', 'Thank you for confirming. The newsletter will arrive in your inbox, and every issue includes a link to unsubscribe.'],
            'confirm:unsubscribed'                 => ['error', 'You are unsubscribed', 'This address was unsubscribed, so we did not confirm it. You can sign up again on the home page.'],
            'unsubscribe:done', 'unsubscribe:already' => ['success', 'You are unsubscribed', 'You will not receive the newsletter any more. If this was a mistake, you can sign up again on the home page.'],
            default                                => ['error', 'This link is not valid', 'Please check that you used the full link from the email.'],
        };
    } else {
        $subscriber = newsletter_find($token);
        if ($subscriber === null) {
            [$title, $message] = ['This link is not valid', 'Please check that you used the full link from the email.'];
        } else {
            $state   = 'ask';
            $message = $kind === 'confirm'
                ? 'Please confirm that you would like to receive the ' . SITE_NAME . ' newsletter at ' . $subscriber['Email'] . '.'
                : 'Would you like to stop receiving the newsletter at ' . $subscriber['Email'] . '?';
            $button  = $kind === 'confirm' ? 'Confirm my subscription' : 'Unsubscribe';
        }
    }
} catch (Throwable $error) {
    error_log('Newsletter ' . $kind . ' failed: ' . $error->getMessage());
    [$title, $message] = ['Something went wrong', 'Please try the link again in a few minutes.'];
}

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$pageTitle       = $title;
$pageDescription = SITE_TAGLINE;
$noindex         = true;

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="login-page">
    <div class="login-card">
        <h1 class="login-card__title"><?= e($title) ?></h1>
<?php if ($state === 'ask'): ?>
        <p class="login-card__intro"><?= e($message) ?></p>
        <form method="post" action="<?= e(url('newsletter.php')) ?>" class="login-form">
            <input type="hidden" name="action" value="<?= e($kind) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <button class="btn btn--primary" type="submit"><?= e($button) ?></button>
        </form>
<?php else: ?>
        <p class="admin-alert admin-alert--<?= $state === 'success' ? 'success' : 'error' ?>" role="status"><?= e($message) ?></p>
        <p><a href="<?= e(url('index.php')) ?>">Go to the home page</a></p>
<?php endif; ?>
    </div>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
