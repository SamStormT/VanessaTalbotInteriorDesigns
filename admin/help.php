<?php
/**
 * Help: how to do the everyday things in the admin area, in plain words, for
 * people with no technical background. Linked from the top bar on every
 * admin screen. Everyone signed in can read it.
 */
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

require_admin();

$helpLink = static fn(string $path, string $label): string => '<a href="' . e(url($path)) . '">' . e($label) . '</a>';

$pageTitle = 'Help';
$layout    = 'admin';
require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content" class="admin-content help">
    <p class="admin-help">Short how-tos for the everyday jobs. Nothing you do here can't be fixed: almost every change can be undone with <strong>History and undo</strong>.</p>

    <nav class="admin-panel help-contents" aria-labelledby="contents-title">
        <h2 class="admin-panel__title" id="contents-title">Contents</h2>
        <ol>
            <li><a href="#change-wording">Change wording or a picture on the website</a></li>
            <li><a href="#undo">Undo a change</a></li>
            <li><a href="#blog">Write and publish a blog article</a></li>
            <li><a href="#newsletter">Send a newsletter</a></li>
            <li><a href="#projects">Add a portfolio project (with before and after)</a></li>
            <li><a href="#testimonials">Add a testimonial</a></li>
            <li><a href="#messages">Answer messages and consultation requests</a></li>
            <li><a href="#found">Help people find the website</a></li>
            <li><a href="#account">Your account and two-step sign-in</a></li>
            <li><a href="#roles">Who can do what</a></li>
        </ol>
    </nav>

    <section class="admin-panel help-topic" id="change-wording" aria-labelledby="t1">
        <h2 class="admin-panel__title" id="t1">1. Change wording or a picture on the website</h2>
        <ol>
            <li>While signed in, open the website (<strong>View site</strong> at the top).</li>
            <li>Move the mouse over the words or picture you want to change: it gets a dotted outline and a label such as <em>Edit: Hero subtitle</em>. Click the label. (On a phone, use the small <strong>Edit</strong> buttons on each block.)</li>
            <li>The right screen opens with the cursor in that exact box. Make the change.</li>
            <li>Choose <strong>Save and return to the page</strong> to see the result straight away.</li>
        </ol>
        <p><strong>To move a photo in its frame or zoom in</strong>: on the website, move the mouse over the photo and click <strong>Adjust photo</strong> (top right of the photo). Drag the photo until the part you want shows, zoom with the slider, the mouse wheel or two fingers, and choose <strong>Save</strong>: the page changes straight away. The frame you came from is marked <em>On this page</em>, and a note warns if the photo would look blurry at that zoom. It applies everywhere that photo is used. The same tool is next to every picture in the admin forms and in the <?= $helpLink('admin/media-library.php', 'Media Library') ?>.</p>
        <p>Dashed strips that say <em>Hidden from visitors</em> are parts of the page that are switched off or empty. Only you can see them; click <strong>Edit and publish</strong> to fill them in. <?= $helpLink('admin/pages.php', 'Pages (what is where)') ?> lists every part of every page.</p>
    </section>

    <section class="admin-panel help-topic" id="undo" aria-labelledby="t2">
        <h2 class="admin-panel__title" id="t2">2. Undo a change</h2>
        <p>Open the screen where you made the change and choose <strong>History and undo</strong>. Every save is listed with what it was before and after. <strong>Undo the last change</strong> puts it back; the undo can itself be undone. The last 50 changes are kept for each item.</p>
        <p>If you were typing an article and the page closed before you saved, open it again: you'll be offered <strong>Put them back</strong>.</p>
    </section>

    <section class="admin-panel help-topic" id="blog" aria-labelledby="t3">
        <h2 class="admin-panel__title" id="t3">3. Write and publish a blog article</h2>
        <ol>
            <li><?= $helpLink('admin/blogs.php', 'Blog Posts') ?> → <strong>New post</strong>. Give it a title, a category and a one- or two-sentence summary.</li>
            <li>Type the article in the big box as you would an email. The buttons above add <strong>bold</strong>, links, headings, lists, quotes, pictures, coloured sections and buttons. <strong>Picture</strong> lets you drag in a photo from your computer or choose one already in the Media Library, then describe it and add a caption.</li>
            <li>Add a featured image: it shows on the blog page and when the article is shared on WhatsApp or Facebook.</li>
            <li>Check <strong>How it may look in Google</strong> under the article and work through any <em>To improve</em> points.</li>
            <li>Choose <strong>Save draft</strong> to finish later, <strong>Publish now</strong>, or <strong>Schedule…</strong> to pick a date and time: it then appears by itself.</li>
            <li>After publishing you'll be offered <strong>Create a newsletter from it</strong>.</li>
        </ol>
        <p>Editors choose <strong>Save and ask an Admin to publish</strong>; the Admins are emailed.</p>
    </section>

    <section class="admin-panel help-topic" id="newsletter" aria-labelledby="t4">
        <h2 class="admin-panel__title" id="t4">4. Send a newsletter</h2>
        <ol>
            <li>Open a published article and choose <strong>Create a newsletter from it</strong>, or go to <?= $helpLink('admin/campaigns.php', 'Newsletter Campaigns') ?> → <strong>New round-up</strong> for the newest articles.</li>
            <li>Everything is filled in. Write a short introduction, then <strong>Save</strong>.</li>
            <li>Choose <strong>Preview</strong> to see it on a computer and a phone, then <strong>Send a test</strong> to yourself and check the email.</li>
            <li><strong>Save and mark ready</strong>, then either type SEND and choose <strong>Send campaign</strong>, or choose a date under <strong>Or send it later</strong>.</li>
        </ol>
        <p>Only people who confirmed their subscription receive it, and every email has an unsubscribe link. New subscribers can be sent a <?= $helpLink('admin/welcome-email.php', 'Welcome Email') ?> (switched off until you turn it on).</p>
    </section>

    <section class="admin-panel help-topic" id="projects" aria-labelledby="t5">
        <h2 class="admin-panel__title" id="t5">5. Add a portfolio project</h2>
        <ol>
            <li><?= $helpLink('admin/portfolio-projects.php', 'Portfolio Projects') ?> → add a project: title, category, description and a featured photo.</li>
            <li>Tick that the client gave permission, and publish. Projects without permission never appear.</li>
            <li>Under the project's gallery, drag in more photos. To show a before and after, open a gallery photo and add a <strong>Before photo</strong> of the same view: the website shows a slider.</li>
            <li>Describe each photo: search engines read the descriptions, and they help people who can't see the pictures.</li>
        </ol>
    </section>

    <section class="admin-panel help-topic" id="testimonials" aria-labelledby="t6">
        <h2 class="admin-panel__title" id="t6">6. Add a testimonial</h2>
        <p><?= $helpLink('admin/testimonials.php', 'Testimonials') ?> → add one with the client's words, their name and town, and the date they gave written permission (it can't be published without it). Choose the service or project it belongs to, so it shows on that page too. Tick <strong>Feature</strong> to show it first on the home page.</p>
    </section>

    <section class="admin-panel help-topic" id="messages" aria-labelledby="t7">
        <h2 class="admin-panel__title" id="t7">7. Answer messages and consultation requests</h2>
        <p>Every message from the contact form and every consultation request arrives in your email and in <?= $helpLink('admin/enquiries.php', 'Contact Messages') ?>. Reply from your own mailbox (the reply goes straight to the person). Then set the message to <strong>Responded</strong>, and later <strong>Closed</strong>. Consultation requests show the type of space, service, area and the two preferred dates at the top.</p>
        <p>Answered and closed messages are removed automatically after the time set in Website Settings → Privacy (24 months to begin with).</p>
    </section>

    <section class="admin-panel help-topic" id="found" aria-labelledby="t8">
        <h2 class="admin-panel__title" id="t8">8. Help people find the website</h2>
        <p><?= $helpLink('admin/search-health.php', 'Search health') ?> lists everything that helps people find the website on Google and in AI assistants such as ChatGPT, with a <strong>Fix</strong> button for each. <?= $helpLink('admin/statistics.php', 'Statistics') ?> shows how many people read each page and where they came from. The most useful habits: a new article every few weeks that answers one question clients ask, a described picture on every page, and asking happy clients for a Google review.</p>
    </section>

    <section class="admin-panel help-topic" id="account" aria-labelledby="t9">
        <h2 class="admin-panel__title" id="t9">9. Your account and two-step sign-in</h2>
        <p><?= $helpLink('admin/account.php', 'My account') ?> (top of every screen) changes your name and password and shows your recent sign-ins. Switch on <strong>two-step sign-in</strong> there: signing in then also needs a code from an app on your phone, so a stolen password alone isn't enough. Keep the backup codes somewhere safe. Forgotten your password? Use <strong>Forgot your password?</strong> on the sign-in page.</p>
    </section>

    <section class="admin-panel help-topic" id="roles" aria-labelledby="t10">
        <h2 class="admin-panel__title" id="t10">10. Who can do what</h2>
        <ul>
            <li><strong>SuperAdmin</strong>: everything, including users, Website Settings and the activity log.</li>
            <li><strong>Admin</strong>: all content, publishing, newsletters, subscribers and messages.</li>
            <li><strong>Editor</strong>: writes and edits content; an Admin publishes it. Editors can't see subscribers or messages, which hold people's personal details.</li>
        </ul>
    </section>
</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
