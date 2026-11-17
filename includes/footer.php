<?php
/**
 * Shared footer + closing layout.
 */

$layout = $layout ?? 'public';

if ($layout !== 'admin') {
    require_once INCLUDES_PATH . '/content.php';
    $footerContent = site_sections('global')['footer'] ?? [];
}

if ($layout === 'admin'): ?>
</div><!-- /.admin-main -->
</div><!-- /.admin-shell -->
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<script src="<?= e(asset('js/visual-editor.js')) ?>" defer></script>
<?php else: ?>
<footer class="site-footer">
    <?= edit_block('global', 'footer') ?>
    <div class="container site-footer__inner">
        <div class="site-footer__brand">
            <p class="brand"><?= brand_logo('light') ?></p>
            <p class="tagline site-footer__tagline"<?= ed_setting('Tagline') ?>><?= e(site_tagline()) ?></p>
            <p class="site-footer__about"<?= ed_section($footerContent ?: null, 'Introduction') ?>><?= e($footerContent['Introduction'] ?? '') ?></p>
        </div>

        <nav class="site-footer__col" aria-labelledby="footer-explore">
            <h2 id="footer-explore" class="site-footer__heading">Explore</h2>
            <ul>
                <li><a href="<?= e(url('about.php')) ?>">About Vanessa</a></li>
                <li><a href="<?= e(url('portfolio.php')) ?>">Portfolio</a></li>
                <li><a href="<?= e(url('portfolio/photography-gallery.php')) ?>">Photography</a></li>
                <li><a href="<?= e(url('services/speaking-engagements.php')) ?>">Speaking</a></li>
            </ul>
        </nav>

        <nav class="site-footer__col" aria-labelledby="footer-services">
            <h2 id="footer-services" class="site-footer__heading">Services</h2>
            <ul>
                <li><a href="<?= e(url('services/biophilic-design.php')) ?>">Biophilic &amp; Well-being Design</a></li>
                <li><a href="<?= e(url('services/colour-psychology.php')) ?>">Colour &amp; Design Psychology</a></li>
                <li><a href="<?= e(url('services/workshops.php')) ?>">Workshops</a></li>
                <li><a href="<?= e(url('services/consultations.php')) ?>">Consultations</a></li>
            </ul>
        </nav>

        <div class="site-footer__col">
            <h2 class="site-footer__heading">Get in touch</h2>
            <p<?= ed_section($footerContent ?: null, 'Body') ?>><?= e($footerContent['Body'] ?? '') ?></p>
            <?= content_link($footerContent['ButtonText'] ?? '', $footerContent['ButtonLink'] ?? '', 'btn btn--light btn--small', '', ed_section($footerContent ?: null, 'ButtonText')) ?>
        </div>
    </div>

    <div class="site-footer__legal">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
            <ul>
                <li><a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a></li>
                <li><a href="<?= e(url('terms-and-conditions.php')) ?>">Terms &amp; Conditions</a></li>
                <li><a href="<?= e(url('copyright.php')) ?>">Copyright</a></li>
            </ul>
        </div>
    </div>
</footer>
<?php
// "Chat on WhatsApp", when a WhatsApp number is set in Website Settings. A local number
// (082 …) is turned into the international form WhatsApp links need (27 82 …).
$whatsApp = preg_replace('/[^0-9]/', '', (string) (site_settings()['WhatsApp'] ?? ''));
if (strlen($whatsApp) === 10 && $whatsApp[0] === '0') {
    $whatsApp = '27' . substr($whatsApp, 1);
}
if (strlen($whatsApp) >= 9): ?>
<a class="whatsapp-button" href="https://wa.me/<?= e($whatsApp) ?>?text=<?= e(rawurlencode('Hello, I am getting in touch from your website.')) ?>" target="_blank" rel="noopener"<?= ed_setting('WhatsApp') ?>><?= icon('chat') ?><span class="whatsapp-button__text">Chat on WhatsApp</span><span class="visually-hidden"> (opens WhatsApp)</span></a>
<?php endif; ?>
<?php
// Count this view for Marketing → Statistics: no cookies or personal details (includes/statistics.php).
// Staff and pages kept out of search engines are not counted.
if (empty($noindex) && empty($staff)) {
    require_once INCLUDES_PATH . '/statistics.php';
    stats_record();
}
// The breadcrumb trail, for search engines (pages with a page header or their own breadcrumb).
if (!empty($GLOBALS['vt_breadcrumbs']) && count($GLOBALS['vt_breadcrumbs']) > 1 && empty($noindex) && mail_origin() !== '') {
    require_once INCLUDES_PATH . '/seo.php';
    echo seo_script(seo_breadcrumbs($GLOBALS['vt_breadcrumbs']));
}
?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
