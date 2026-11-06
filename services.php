<?php
/**
 * Services overview page
 */
require_once __DIR__ . '/includes/config.php';
require_once INCLUDES_PATH . '/site-data.php';

$pageTitle       = 'Services';
$pageDescription = 'Biophilic design, colour psychology, photography, workshops, speaking engagements and consultations by Vanessa Talbot Interior Designs.';

$tones = ['green', 'peach', 'gold'];

$process = [
    ['title' => 'Listen',  'text' => 'We start with a conversation about your space, the people who use it and how you want it to feel.'],
    ['title' => 'Explore', 'text' => 'Vanessa studies light, colour, layout and your connection to the outdoors, and looks for opportunities.'],
    ['title' => 'Design',  'text' => 'You receive clear, considered recommendations that balance beauty, wellbeing and practicality.'],
    ['title' => 'Bring to life', 'text' => 'Whether you implement the plan yourself or with Vanessa\'s support, your space begins to transform.'],
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
                    <li aria-current="page">Services</li>
                </ol>
            </nav>
            <h1 id="page-title">Services</h1>
            <p class="page-hero__lead">
                Every service combines the science of how people respond to their surroundings with the art of
                beautiful design, for homes, workspaces, wellness centres, boutiques and events.
            </p>
        </div>
    </section>

    <nav class="service-nav" aria-label="Jump to a service">
        <div class="container">
            <ul class="service-nav__list">
<?php foreach ($services as $service): ?>
                <li><a href="#<?= e($service['slug']) ?>"><?= icon($service['icon'], 'icon icon--inline') ?> <?= e($service['title']) ?></a></li>
<?php endforeach; ?>
            </ul>
        </div>
    </nav>

<?php foreach ($services as $i => $service): ?>
    <section id="<?= e($service['slug']) ?>" class="section service-section<?= $i % 2 ? ' section--peach' : '' ?>" aria-labelledby="<?= e($service['slug']) ?>-title">
        <div class="container split<?= $i % 2 ? ' split--reverse' : '' ?>">
            <div class="split__media">
                <div class="art-frame art-frame--<?= e($tones[$i % 3]) ?>" aria-hidden="true">
                    <?= icon($service['icon'], 'icon art-frame__icon') ?>
                </div>
            </div>
            <div class="split__content">
                <p class="eyebrow"><span class="service-section__number"><?= sprintf('%02d', $i + 1) ?></span> Service</p>
                <h2 id="<?= e($service['slug']) ?>-title"><?= e($service['title']) ?></h2>
                <p class="lead"><?= e($service['lead']) ?></p>
                <p><?= e($service['body']) ?></p>

                <h3 class="subheading">What's included</h3>
                <ul class="check-list">
<?php foreach ($service['includes'] as $item): ?>
                    <li><?= icon('check') ?><span><?= e($item) ?></span></li>
<?php endforeach; ?>
                </ul>

                <h3 class="subheading">Ideal for</h3>
                <ul class="tag-list" aria-label="<?= e($service['title']) ?> is ideal for">
<?php foreach ($service['ideal'] as $market): ?>
                    <li><?= e($market) ?></li>
<?php endforeach; ?>
                </ul>

                <div class="btn-group">
                    <a class="btn btn--dark" href="<?= e(url($service['url'])) ?>">
                        More about <?= e(strtolower($service['title'])) ?>
                    </a>
                    <a class="btn btn--outline" href="<?= e(url('contact.php?service=' . urlencode($service['slug']))) ?>">
                        Enquire<span class="visually-hidden"> about <?= e(strtolower($service['title'])) ?></span>
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endforeach; ?>

    <!-- Process -->
    <section class="section section--white" aria-labelledby="process-title">
        <div class="container">
            <header class="section-header">
                <p class="eyebrow">How it works</p>
                <h2 id="process-title">A simple, collaborative process</h2>
            </header>
            <ol class="process">
<?php foreach ($process as $step): ?>
                <li class="process__step">
                    <h3><?= e($step['title']) ?></h3>
                    <p><?= e($step['text']) ?></p>
                </li>
<?php endforeach; ?>
            </ol>
        </div>
    </section>

    <!-- Call-to-action -->
    <section class="section cta-banner" aria-labelledby="services-cta-title">
        <div class="container cta-banner__inner">
            <h2 id="services-cta-title">Not sure where to start?</h2>
            <p>
                A consultation is the perfect first step. Vanessa will help you understand your space and
                recommend the approach that suits you best.
            </p>
            <div class="btn-group btn-group--centred">
                <a class="btn btn--primary" href="<?= e(url('services/consultations.php')) ?>">Book a consultation</a>
                <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>">Ask a question</a>
            </div>
        </div>
    </section>

</main>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
