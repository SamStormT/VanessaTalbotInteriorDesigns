<?php
/**
 * Home page
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/site-data.php';

$pageTitle       = '';
$pageDescription = 'Vanessa Talbot Interior Designs creates nature-centred interiors through biophilic design, colour psychology and photography, for homes, workspaces, wellness centres, boutiques and events.';

// Latest blog posts. Loaded from the database in a later phase; empty until then.
$latestPosts = [];

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <!-- Hero -->
    <section class="hero" aria-labelledby="hero-title">
        <?= botanical('botanical hero__botanical') ?>
        <div class="container hero__inner">
            <p class="eyebrow eyebrow--light">Biophilic Interior Design &middot; Colour Psychology &middot; Photography</p>
            <h1 id="hero-title" class="display-title hero__title">Vanessa Talbot</h1>
            <p class="tagline hero__tagline"><?= e(SITE_TAGLINE) ?></p>
            <p class="hero__lead">
                Interiors designed around the way people are meant to live: connected to light, colour
                and the natural world. Grounded in science, shaped by art, inspired by nature.
            </p>
            <div class="btn-group">
                <a class="btn btn--primary" href="<?= e(url('services/consultations.php')) ?>">Book a consultation</a>
                <a class="btn btn--outline-light" href="<?= e(url('services.php')) ?>">Explore services</a>
            </div>
        </div>
    </section>

    <!-- Featured call-to-action -->
    <section class="feature-cta" aria-labelledby="feature-cta-title">
        <div class="container">
            <div class="feature-cta__card">
                <span class="feature-cta__icon"><?= icon('leaf') ?></span>
                <div class="feature-cta__text">
                    <h2 id="feature-cta-title" class="feature-cta__title">Bring nature back into your space</h2>
                    <p>
                        Start with a one-on-one consultation. Together we'll look at how your space feels,
                        how you use it, and the simple, meaningful changes that will make the biggest difference.
                    </p>
                </div>
                <a class="btn btn--primary" href="<?= e(url('services/consultations.php')) ?>">
                    Start with a consultation <?= icon('arrow', 'icon icon--inline') ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Introduction to Vanessa -->
    <section class="section" aria-labelledby="intro-title">
        <div class="container split">
            <div class="split__media">
                <div class="art-frame art-frame--portrait art-frame--green" aria-hidden="true">
                    <?= botanical('botanical art-frame__botanical') ?>
                </div>
            </div>
            <div class="split__content">
                <p class="eyebrow">Meet Vanessa</p>
                <h2 id="intro-title">Where science, art and nature meet</h2>
                <p class="lead">
                    Vanessa Talbot brings together a background in science and a passion for interior design
                    to create spaces that do more than look beautiful. They help people feel well.
                </p>
                <p>
                    Her work is built on a simple idea: the spaces we spend our days in shape how we think,
                    feel and connect. By combining biophilic design, the psychology of colour and a
                    photographer's eye for light and detail, Vanessa designs interiors that are calm,
                    personal and deeply connected to the natural world.
                </p>
                <ul class="pillars" aria-label="The three foundations of Vanessa's work">
                    <li class="pillar"><?= icon('brush') ?><span><strong>Art</strong> Beauty, harmony and creative expression</span></li>
                    <li class="pillar"><?= icon('flask') ?><span><strong>Science</strong> Evidence-informed design decisions</span></li>
                    <li class="pillar"><?= icon('sprout') ?><span><strong>Nature</strong> Living, restorative connection</span></li>
                </ul>
                <a class="text-link" href="<?= e(url('about.php')) ?>">Read Vanessa's story <?= icon('arrow', 'icon icon--inline') ?></a>
            </div>
        </div>
    </section>

    <!-- Services overview -->
    <section class="section section--peach" aria-labelledby="services-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">Services</p>
                <h2 id="services-title">Designing with nature, in every sense</h2>
                <p>
                    From a single consultation to a complete biophilic redesign, every service is shaped
                    around the people who will use the space.
                </p>
            </header>

            <ul class="card-grid card-grid--3">
<?php foreach ($services as $service): ?>
                <li class="card">
                    <span class="card__icon"><?= icon($service['icon']) ?></span>
                    <h3 class="card__title">
                        <a class="card__link" href="<?= e(url($service['url'])) ?>"><?= e($service['title']) ?></a>
                    </h3>
                    <p><?= e($service['summary']) ?></p>
                    <span class="card__more" aria-hidden="true">Learn more <?= icon('arrow', 'icon icon--inline') ?></span>
                </li>
<?php endforeach; ?>
            </ul>

            <p class="section-footer">
                <a class="btn btn--dark" href="<?= e(url('services.php')) ?>">View all services</a>
            </p>
        </div>
    </section>

    <!-- Featured portfolio -->
    <section class="section" aria-labelledby="portfolio-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">Portfolio</p>
                <h2 id="portfolio-title">Spaces with a sense of place</h2>
                <p>Explore Vanessa's work across biophilic interiors, colour-led design and photography.</p>
            </header>

            <ul class="portfolio-grid">
<?php foreach ($portfolioCategories as $category): ?>
                <li class="portfolio-tile">
                    <div class="art-frame art-frame--<?= e($category['tone']) ?>" aria-hidden="true">
                        <?= icon($category['icon'], 'icon art-frame__icon') ?>
                    </div>
                    <div class="portfolio-tile__body">
                        <h3 class="portfolio-tile__title">
                            <a class="card__link" href="<?= e(url($category['url'])) ?>"><?= e($category['title']) ?></a>
                        </h3>
                        <p><?= e($category['text']) ?></p>
                    </div>
                </li>
<?php endforeach; ?>
            </ul>

            <p class="section-footer">
                <a class="text-link" href="<?= e(url('portfolio.php')) ?>">View the full portfolio <?= icon('arrow', 'icon icon--inline') ?></a>
            </p>
        </div>
    </section>

    <!-- Latest blog -->
    <section class="section section--white" aria-labelledby="blog-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">From the blog</p>
                <h2 id="blog-title">Ideas for living closer to nature</h2>
            </header>

<?php if ($latestPosts): ?>
            <ul class="card-grid card-grid--3">
<?php foreach ($latestPosts as $post): ?>
                <li class="card card--post">
                    <p class="card__meta"><?= e($post['category']) ?> &middot; <time datetime="<?= e($post['date']) ?>"><?= e(date('j F Y', strtotime($post['date']))) ?></time></p>
                    <h3 class="card__title"><a class="card__link" href="<?= e(url('blog/blog-post.php?slug=' . urlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h3>
                    <p><?= e($post['excerpt']) ?></p>
                </li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <div class="empty-state">
                <span class="empty-state__icon"><?= icon('pen') ?></span>
                <p class="empty-state__title">The first articles are on their way</p>
                <p>
                    Vanessa will be sharing practical ideas on biophilic design, the psychology of colour,
                    nature-centred wellbeing and the stories behind her photography.
                    Join the newsletter below to be the first to read them.
                </p>
                <ul class="tag-list" aria-label="Upcoming blog topics">
                    <li>Biophilic design</li>
                    <li>Colour psychology</li>
                    <li>Wellbeing at home</li>
                    <li>Healthier workspaces</li>
                    <li>Photography</li>
                </ul>
            </div>
<?php endif; ?>
        </div>
    </section>

    <!-- Newsletter -->
    <section id="newsletter" class="section section--green newsletter" aria-labelledby="newsletter-title">
        <div class="container newsletter__inner">
            <div class="newsletter__text">
                <p class="eyebrow eyebrow--light">Newsletter</p>
                <h2 id="newsletter-title">Nature-inspired ideas, delivered to your inbox</h2>
                <p>
                    Seasonal inspiration, colour and design tips, new photography prints, and first news
                    of upcoming workshops and talks. No clutter, just ideas worth your time.
                </p>
            </div>

            <form class="newsletter__form" action="<?= e(url('contact.php')) ?>" method="get" data-newsletter-form novalidate>
                <div class="form-field">
                    <label for="newsletter-name">First name</label>
                    <input id="newsletter-name" name="name" type="text" autocomplete="given-name">
                </div>
                <div class="form-field">
                    <label for="newsletter-email">Email address <span class="required" aria-hidden="true">*</span></label>
                    <input id="newsletter-email" name="email" type="email" autocomplete="email" required aria-describedby="newsletter-status">
                </div>
                <button class="btn btn--primary" type="submit"><?= icon('mail', 'icon icon--inline') ?> Subscribe</button>
                <p id="newsletter-status" class="form-status" role="status" aria-live="polite"></p>
                <p class="form-note">
                    Your details are only used to send the newsletter. See our
                    <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.
                </p>
            </form>
        </div>
    </section>

    <!-- Contact call-to-action -->
    <section class="section cta-banner" aria-labelledby="contact-cta-title">
        <div class="container cta-banner__inner">
            <h2 id="contact-cta-title">Let's create a space that feels like you</h2>
            <p>
                Whether it's your home, your workspace, a wellness centre, a boutique or an event,
                Vanessa would love to hear what you have in mind.
            </p>
            <ul class="tag-list tag-list--centred" aria-label="Who Vanessa works with">
<?php foreach ($targetMarkets as $market): ?>
                <li><?= e($market) ?></li>
<?php endforeach; ?>
            </ul>
            <div class="btn-group btn-group--centred">
                <a class="btn btn--primary" href="<?= e(url('contact.php')) ?>">Get in touch</a>
                <a class="btn btn--outline" href="<?= e(url('portfolio.php')) ?>">See the portfolio</a>
            </div>
        </div>
    </section>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
