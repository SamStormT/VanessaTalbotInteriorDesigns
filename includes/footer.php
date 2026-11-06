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
            <p class="brand"><?= brand_logo('light') ?></p>
            <p class="tagline site-footer__tagline"><?= e(SITE_TAGLINE) ?></p>
            <p class="site-footer__about">
                Biophilic interior design, colour psychology and photography for homes,
                workspaces, wellness centres, boutiques and events.
            </p>
        </div>

        <nav class="site-footer__col" aria-labelledby="footer-explore">
            <h2 id="footer-explore" class="site-footer__heading">Explore</h2>
            <ul>
                <li><a href="<?= e(url('about.php')) ?>">About Vanessa</a></li>
                <li><a href="<?= e(url('portfolio.php')) ?>">Portfolio</a></li>
                <li><a href="<?= e(url('photography.php')) ?>">Photography</a></li>
                <li><a href="<?= e(url('blog.php')) ?>">Blog</a></li>
                <li><a href="<?= e(url('speaking.php')) ?>">Speaking</a></li>
            </ul>
        </nav>

        <nav class="site-footer__col" aria-labelledby="footer-services">
            <h2 id="footer-services" class="site-footer__heading">Services</h2>
            <ul>
                <li><a href="<?= e(url('services/biophilic-design.php')) ?>">Biophilic Design</a></li>
                <li><a href="<?= e(url('services/colour-psychology.php')) ?>">Colour Psychology</a></li>
                <li><a href="<?= e(url('services/workshops.php')) ?>">Workshops</a></li>
                <li><a href="<?= e(url('services/consultations.php')) ?>">Consultations</a></li>
            </ul>
        </nav>

        <div class="site-footer__col">
            <h2 class="site-footer__heading">Get in touch</h2>
            <p>Ready to bring nature into your space? Let's talk about what you have in mind.</p>
            <a class="btn btn--light btn--small" href="<?= e(url('contact.php')) ?>">Contact Vanessa</a>
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
