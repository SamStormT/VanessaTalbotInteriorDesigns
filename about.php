<?php
/**
 * About page
 *
 * REVIEW: Vanessa's story is written from the brand documentation only
 * (a background in science, then interior design). Personal details such as
 * her specific field of science, studies, dates and location should be
 * confirmed or added by Vanessa before launch.
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/site-data.php';

$pageTitle       = 'About Vanessa';
$pageDescription = 'Meet Vanessa Talbot: an interior designer with a background in science, creating biophilic, colour-led spaces that support wellbeing and reconnect people with nature.';

$speakingTopics = [
    'Why nature belongs indoors: the case for biophilic design',
    'The psychology of colour in everyday spaces',
    'Designing workspaces that support focus and wellbeing',
    'Seeing like a photographer: light, texture and detail at home',
];

require_once INCLUDES_PATH . '/header.php';
?>
<main id="main-content">

    <section class="page-hero" aria-labelledby="page-title">
        <?= botanical('botanical page-hero__botanical') ?>
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <ol>
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li aria-current="page">About</li>
                </ol>
            </nav>
            <h1 id="page-title">About Vanessa</h1>
            <p class="tagline page-hero__tagline"><?= e(SITE_TAGLINE) ?></p>
        </div>
    </section>

    <!-- Vanessa's story -->
    <section class="section" aria-labelledby="story-title">
        <div class="container split">
            <div class="split__media">
                <div class="art-frame art-frame--portrait art-frame--peach" aria-hidden="true">
                    <?= botanical('botanical art-frame__botanical') ?>
                </div>
            </div>
            <div class="split__content">
                <p class="eyebrow">Vanessa's story</p>
                <h2 id="story-title">Designing spaces that help people thrive</h2>
                <p class="lead">
                    Vanessa Talbot has always been fascinated by the relationship between people and the
                    places they spend their lives in.
                </p>
                <p>
                    It is a curiosity that has carried her from science to interior design, and into
                    photography. Each one has taught her to look a little more closely, at how light moves
                    through a room, how colour changes a mood, and how a single living plant can soften
                    an entire space.
                </p>
                <p>
                    Today, Vanessa Talbot Interior Designs brings those threads together. Vanessa works with
                    homeowners, businesses, wellness practitioners, retailers and event organisers to create
                    interiors that are beautiful, personal and good for the people in them.
                </p>
            </div>
        </div>
    </section>

    <!-- Science to interior design journey -->
    <section class="section section--peach" aria-labelledby="journey-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">The journey</p>
                <h2 id="journey-title">From science to interior design</h2>
                <p>
                    Vanessa's path into design is what makes her approach different. She doesn't see
                    science and creativity as opposites. To her, they are two ways of understanding the same world.
                </p>
            </header>

            <ol class="journey">
                <li class="journey__step">
                    <span class="journey__icon"><?= icon('flask') ?></span>
                    <h3>A scientific foundation</h3>
                    <p>
                        A background in science taught Vanessa to observe carefully, ask questions and look for
                        evidence. It also gave her a lasting respect for the natural systems that sustain us.
                    </p>
                </li>
                <li class="journey__step">
                    <span class="journey__icon"><?= icon('brush') ?></span>
                    <h3>A creative calling</h3>
                    <p>
                        Her love of beauty, colour and composition drew her to interior design, where she
                        discovered she could use both sides of her thinking at once.
                    </p>
                </li>
                <li class="journey__step">
                    <span class="journey__icon"><?= icon('sprout') ?></span>
                    <h3>Nature as the bridge</h3>
                    <p>
                        Biophilic design and colour psychology brought it all together: design choices informed
                        by how people actually respond to their surroundings, expressed with an artist's eye.
                    </p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Biophilic design philosophy -->
    <section class="section" aria-labelledby="philosophy-title">
        <div class="container split split--reverse">
            <div class="split__media">
                <figure class="quote-panel">
                    <blockquote>
                        <p>&ldquo;<?= e(SITE_TAGLINE) ?>.&rdquo;</p>
                    </blockquote>
                    <figcaption><?= e(SITE_NAME) ?></figcaption>
                </figure>
            </div>
            <div class="split__content">
                <p class="eyebrow">Philosophy</p>
                <h2 id="philosophy-title">A biophilic approach to design</h2>
                <p class="lead">
                    Biophilia is our innate need to connect with nature. Vanessa's work starts from the belief
                    that this connection is not a luxury. It is essential to how we rest, focus and feel.
                </p>
                <p>In practice, that means designing with:</p>
                <ul class="check-list">
                    <li><?= icon('check') ?><span><strong>Light and air</strong>: making the most of daylight, fresh air and views to the outside.</span></li>
                    <li><?= icon('check') ?><span><strong>Living elements</strong>: plants, water and natural features that bring a space to life.</span></li>
                    <li><?= icon('check') ?><span><strong>Natural materials</strong>: wood, stone, linen and texture that invite touch and feel grounded.</span></li>
                    <li><?= icon('check') ?><span><strong>Colour with purpose</strong>: palettes drawn from nature and chosen for how they make people feel.</span></li>
                    <li><?= icon('check') ?><span><strong>Organic form</strong>: shapes, patterns and layouts that echo the natural world.</span></li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="section section--green" aria-labelledby="values-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow eyebrow--light">Values</p>
                <h2 id="values-title">What guides every project</h2>
            </header>
            <ul class="values-grid">
<?php foreach ($coreValues as $value): ?>
                <li class="value">
                    <span class="value__icon"><?= icon($value['icon']) ?></span>
                    <h3 class="value__title"><?= e($value['title']) ?></h3>
                    <p><?= e($value['text']) ?></p>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Qualifications (placeholder) -->
    <section class="section" aria-labelledby="qualifications-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">Qualifications</p>
                <h2 id="qualifications-title">Training and expertise</h2>
                <p>
                    Vanessa's practice is built on her background in science and interior design,
                    and an ongoing commitment to learning.
                </p>
            </header>
            <?php /* REVIEW: Replace with Vanessa's confirmed qualifications, institutions and memberships. */ ?>
            <div class="info-panel">
                <span class="info-panel__icon"><?= icon('shield') ?></span>
                <div>
                    <p class="info-panel__title">Full details coming soon</p>
                    <p>
                        A complete list of Vanessa's qualifications, professional memberships and accreditations
                        will be published here shortly. For details in the meantime, please
                        <a href="<?= e(url('contact.php')) ?>">get in touch</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Speaking engagements (placeholder) -->
    <section class="section section--peach" aria-labelledby="speaking-title">
        <div class="container split">
            <div class="split__content">
                <p class="eyebrow">Speaking</p>
                <h2 id="speaking-title">Sharing the message</h2>
                <p class="lead">
                    Vanessa speaks to audiences about why our surroundings matter so much, and how anyone can
                    bring more nature, colour and calm into the places they live and work.
                </p>
                <p>Talks are tailored to each audience. Popular themes include:</p>
                <ul class="check-list">
<?php foreach ($speakingTopics as $topic): ?>
                    <li><?= icon('check') ?><span><?= e($topic) ?></span></li>
<?php endforeach; ?>
                </ul>
                <?php /* Phase 3+: list upcoming and past engagements from the database. */ ?>
                <p class="muted">Upcoming and past speaking engagements will be listed here soon.</p>
                <div class="btn-group">
                    <a class="btn btn--dark" href="<?= e(url('speaking.php')) ?>">Speaking engagements</a>
                    <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>">Invite Vanessa to speak</a>
                </div>
            </div>
            <div class="split__media">
                <div class="art-frame art-frame--gold" aria-hidden="true">
                    <?= icon('mic', 'icon art-frame__icon') ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Call-to-action -->
    <section class="section cta-banner" aria-labelledby="about-cta-title">
        <div class="container cta-banner__inner">
            <h2 id="about-cta-title">Ready to reconnect your space with nature?</h2>
            <p>
                Start with a conversation. Tell Vanessa about your space, how you use it and how you'd
                like it to feel.
            </p>
            <div class="btn-group btn-group--centred">
                <a class="btn btn--primary" href="<?= e(url('services/consultations.php')) ?>">Book a consultation</a>
                <a class="btn btn--outline" href="<?= e(url('services.php')) ?>">Explore services</a>
            </div>
        </div>
    </section>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
