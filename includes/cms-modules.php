<?php
/**
 * Field definitions for the CMS framework.
 *
 * A new editable module is one more entry here: includes/cms.php handles the
 * view, form, validation, saving and audit trail. Table and field names come
 * only from this file, never from user input.
 *
 * Field types: text, textarea, lines, email, tel, url, link, path, slug, int,
 * select, checkbox. Optional keys: label, group, required, max, help,
 * options, permission (a stricter permission needed to change the field).
 */

declare(strict_types=1);

function cms_modules(): array
{
    $meta = [
        'MetaTitle'       => ['label' => 'Search result title', 'type' => 'text', 'max' => 255, 'group' => 'Search engines'],
        'MetaDescription' => ['label' => 'Search result description', 'type' => 'textarea', 'max' => 500, 'group' => 'Search engines'],
    ];

    return [
        'settings' => [
            'title'      => 'Website Settings',
            'table'      => 'WebsiteSettings',
            'key'        => 'SettingID',
            'permission' => 'settings',
            'page'       => 'admin/settings.php',
            'fields'     => [
                'BusinessName'    => ['label' => 'Business name', 'type' => 'text', 'required' => true, 'max' => 150, 'group' => 'Business'],
                'Tagline'         => ['label' => 'Tagline', 'type' => 'text', 'max' => 255, 'group' => 'Business'],
                'Phone'           => ['label' => 'Phone', 'type' => 'tel', 'max' => 30, 'group' => 'Contact'],
                'Email'           => ['label' => 'Email', 'type' => 'email', 'max' => 254, 'group' => 'Contact'],
                'City'            => ['label' => 'City', 'type' => 'text', 'max' => 100, 'group' => 'Location'],
                'Province'        => ['label' => 'Province', 'type' => 'text', 'max' => 100, 'group' => 'Location'],
                'Country'         => ['label' => 'Country', 'type' => 'text', 'max' => 100, 'group' => 'Location'],
                'ShowAddress'     => ['label' => 'Show the full street address on the website', 'type' => 'checkbox', 'group' => 'Location', 'help' => 'Leave off to keep the street address private. City and province can still be shown.'],
                'Facebook'        => ['label' => 'Facebook', 'type' => 'url', 'max' => 500, 'group' => 'Social media'],
                'Instagram'       => ['label' => 'Instagram', 'type' => 'url', 'max' => 500, 'group' => 'Social media'],
                'LinkedIn'        => ['label' => 'LinkedIn', 'type' => 'url', 'max' => 500, 'group' => 'Social media'],
                'Pinterest'       => ['label' => 'Pinterest', 'type' => 'url', 'max' => 500, 'group' => 'Social media'],
                'FooterCopyright' => ['label' => 'Footer copyright text', 'type' => 'text', 'max' => 255, 'group' => 'Footer'],
            ],
        ],

        'homepage' => [
            'title'      => 'Homepage Content',
            'table'      => 'HomePageContent',
            'key'        => 'HomePageID',
            'permission' => 'homepage',
            'page'       => 'admin/homepage-content.php',
            'fields'     => [
                'HeroEyebrow'          => ['label' => 'Small heading above the title', 'type' => 'text', 'max' => 255, 'group' => 'Hero'],
                'HeroTitle'            => ['label' => 'Hero title', 'type' => 'text', 'max' => 255, 'group' => 'Hero'],
                'HeroSubtitle'         => ['label' => 'Hero subtitle', 'type' => 'textarea', 'max' => 500, 'group' => 'Hero'],
                'HeroImage'            => ['label' => 'Hero image path', 'type' => 'path', 'max' => 500, 'group' => 'Hero', 'help' => 'For example uploads/home/hero.jpg'],
                'HeroImageAlt'         => ['label' => 'Hero image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Hero'],
                'CTA1Text'             => ['label' => 'First button text', 'type' => 'text', 'max' => 100, 'group' => 'Hero buttons'],
                'CTA1Link'             => ['label' => 'First button link', 'type' => 'link', 'max' => 500, 'group' => 'Hero buttons'],
                'CTA2Text'             => ['label' => 'Second button text', 'type' => 'text', 'max' => 100, 'group' => 'Hero buttons'],
                'CTA2Link'             => ['label' => 'Second button link', 'type' => 'link', 'max' => 500, 'group' => 'Hero buttons'],
                'FeaturedCTATitle'     => ['label' => 'Featured box title', 'type' => 'text', 'max' => 255, 'group' => 'Featured call to action'],
                'FeaturedCTAText'      => ['label' => 'Featured box text', 'type' => 'textarea', 'max' => 5000, 'group' => 'Featured call to action'],
                'FeaturedCTALink'      => ['label' => 'Featured box link', 'type' => 'link', 'max' => 500, 'group' => 'Featured call to action'],
                'IntroductionText'     => ['label' => 'Introduction text', 'type' => 'textarea', 'max' => 20000, 'group' => 'Introduction'],
                'IntroductionImage'    => ['label' => 'Introduction image path', 'type' => 'path', 'max' => 500, 'group' => 'Introduction'],
                'IntroductionImageAlt' => ['label' => 'Introduction image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Introduction'],
                'FeaturedQuote'        => ['label' => 'Quote', 'type' => 'textarea', 'max' => 5000, 'group' => 'Featured quote'],
                'FeaturedQuoteAuthor'  => ['label' => 'Quote author', 'type' => 'text', 'max' => 150, 'group' => 'Featured quote'],
            ] + $meta,
        ],

        'about' => [
            'title'      => 'About Content',
            'table'      => 'AboutContent',
            'key'        => 'AboutID',
            'permission' => 'about',
            'page'       => 'admin/about-content.php',
            'fields'     => [
                'Biography'            => ['label' => 'Biography', 'type' => 'textarea', 'max' => 50000, 'group' => "Vanessa's story"],
                'PortraitImage'        => ['label' => 'Portrait image path', 'type' => 'path', 'max' => 500, 'group' => "Vanessa's story"],
                'PortraitImageAlt'     => ['label' => 'Portrait description (alt text)', 'type' => 'text', 'max' => 255, 'group' => "Vanessa's story"],
                'Journey'              => ['label' => 'Journey', 'type' => 'textarea', 'max' => 50000, 'group' => 'From science to interior design'],
                'ScienceBackground'    => ['label' => 'Science background', 'type' => 'textarea', 'max' => 50000, 'group' => 'From science to interior design'],
                'DesignBackground'     => ['label' => 'Design background', 'type' => 'textarea', 'max' => 50000, 'group' => 'From science to interior design'],
                'Philosophy'           => ['label' => 'Biophilic design philosophy', 'type' => 'textarea', 'max' => 50000, 'group' => 'Philosophy and values'],
                'CoreValues'           => ['label' => 'Values introduction', 'type' => 'textarea', 'max' => 50000, 'group' => 'Philosophy and values'],
                'Qualifications'       => ['label' => 'Qualifications introduction', 'type' => 'textarea', 'max' => 50000, 'group' => 'Qualifications'],
                'SpeakingIntroduction' => ['label' => 'Speaking introduction', 'type' => 'textarea', 'max' => 5000, 'group' => 'Speaking'],
                'SpeakingTopics'       => ['label' => 'Speaking topics', 'type' => 'lines', 'max' => 5000, 'group' => 'Speaking', 'help' => 'One topic per line.'],
            ] + $meta,
        ],

        'services' => [
            'title'      => 'Services',
            'table'      => 'Services',
            'key'        => 'ServiceID',
            'permission' => 'services',
            'page'       => 'admin/service-edit.php',
            'fields'     => [
                'ServiceName'      => ['label' => 'Service name', 'type' => 'text', 'required' => true, 'max' => 150, 'group' => 'Service'],
                'Slug'             => ['label' => 'Web address', 'type' => 'slug', 'required' => true, 'max' => 200, 'group' => 'Service', 'permission' => 'services.publish', 'help' => 'Lower-case letters, numbers and hyphens, for example biophilic-design.'],
                'Status'           => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'group' => 'Service', 'permission' => 'services.publish'],
                'DisplayOrder'     => ['label' => 'Display order', 'type' => 'int', 'group' => 'Service', 'help' => 'Lower numbers appear first.'],
                'Icon'             => ['label' => 'Icon', 'type' => 'select', 'options' => ['leaf', 'palette', 'camera', 'mic', 'group', 'chat', 'sprout', 'brush', 'eye', 'pen'], 'group' => 'Service'],
                'ShortDescription' => ['label' => 'Short description', 'type' => 'textarea', 'max' => 500, 'group' => 'Wording', 'help' => 'Shown on the home page card.'],
                'Lead'             => ['label' => 'Opening sentence', 'type' => 'textarea', 'max' => 500, 'group' => 'Wording'],
                'FullDescription'  => ['label' => 'Full description', 'type' => 'textarea', 'max' => 50000, 'group' => 'Wording'],
                'IncludedItems'    => ['label' => "What's included", 'type' => 'lines', 'max' => 5000, 'group' => 'Wording', 'help' => 'One item per line.'],
                'IdealFor'         => ['label' => 'Ideal for', 'type' => 'lines', 'max' => 2000, 'group' => 'Wording', 'help' => 'One client type per line.'],
                'FeaturedImage'    => ['label' => 'Image path', 'type' => 'path', 'max' => 500, 'group' => 'Image'],
                'FeaturedImageAlt' => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Image'],
            ] + $meta,
        ],
    ];
}
