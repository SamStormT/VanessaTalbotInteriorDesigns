<?php
/**
 * Shared footer + closing layout.
 */

$layout = $layout ?? 'public';

if ($layout === 'admin'): ?>
</div><!-- /.admin-main -->
</div><!-- /.admin-shell -->
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php else: ?>
<footer class="site-footer">
    <div class="container site-footer__inner">
        <div class="site-footer__brand">
            <p class="brand__name"><?= e(SITE_NAME) ?></p>
            <p class="brand__tagline"><?= e(SITE_TAGLINE) ?></p>
        </div>

        <nav class="site-footer__nav" aria-label="Footer navigation">
            <ul>
                <li><a href="<?= e(url('services.php')) ?>">Services</a></li>
                <li><a href="<?= e(url('portfolio.php')) ?>">Portfolio</a></li>
                <li><a href="<?= e(url('photography.php')) ?>">Photography</a></li>
                <li><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
                <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
            </ul>
        </nav>

        <div class="site-footer__newsletter">
            <!-- Phase 2+: newsletter subscription form -->
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
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
