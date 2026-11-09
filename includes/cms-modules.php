<?php
/**
 * Field definitions for the CMS framework.
 *
 * A new editable module is one more entry here: includes/cms.php handles the
 * view, form, validation, saving and audit trail. Table and field names come
 * only from this file, never from user input.
 *
 * Field types: text, textarea, lines, email, tel, url, link, path, slug, int,
 * select, checkbox, date. Optional keys: label, group, required, max, help,
 * options, nullable, auto_from (build a slug from another field), permission
 * (a stricter permission needed to change the field).
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
                'CoreValues'           => ['label' => 'Values introduction', 'type' => 'textarea', 'max' => 50000, 'group' => 'Philosophy and values'],
                'Qualifications'       => ['label' => 'Qualifications introduction', 'type' => 'textarea', 'max' => 50000, 'group' => 'Qualifications'],
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

        'about_values' => [
            'title'      => 'About Values',
            'table'      => 'AboutValues',
            'key'        => 'ValueID',
            'label'      => 'Title',
            'permission' => 'about_items',
            'list_page'  => 'admin/about-values.php',
            'edit_page'  => 'admin/about-value-edit.php',
            'order'      => 't.DisplayOrder, t.Title',
            'list'       => ['Title', 'Description', 'DisplayOrder', 'Status'],
            'fields'     => [
                'Title'        => ['label' => 'Value', 'type' => 'text', 'required' => true, 'max' => 100],
                'Description'  => ['label' => 'Description', 'type' => 'textarea', 'max' => 2000],
                'Icon'         => ['label' => 'Icon', 'type' => 'select', 'options' => cms_icon_options()],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'       => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'about_items.publish'],
            ],
        ],

        'qualifications' => [
            'title'      => 'Qualifications',
            'table'      => 'AboutQualifications',
            'key'        => 'QualificationID',
            'label'      => 'Title',
            'permission' => 'about_items',
            'list_page'  => 'admin/qualifications.php',
            'edit_page'  => 'admin/qualification-edit.php',
            'order'      => 't.DisplayOrder, t.Title',
            'list'       => ['Title', 'Type', 'Institution', 'YearCompleted', 'Status'],
            'empty'      => 'Add qualifications, certifications, memberships and awards once they are confirmed.',
            'fields'     => [
                'Title'         => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255],
                'Type'          => ['label' => 'Type', 'type' => 'select', 'required' => true, 'options' => ['Qualification', 'Certification', 'Membership', 'Award']],
                'Institution'   => ['label' => 'Institution', 'type' => 'text', 'max' => 255],
                'FieldOfStudy'  => ['label' => 'Field of study', 'type' => 'text', 'max' => 255],
                'YearCompleted' => ['label' => 'Year', 'type' => 'int', 'nullable' => true],
                'ExpiryDate'    => ['label' => 'Expiry date', 'type' => 'date'],
                'DisplayOrder'  => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'        => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'about_items.publish'],
            ],
        ],

        'speaking' => [
            'title'      => 'Speaking Engagements',
            'table'      => 'SpeakingEngagements',
            'key'        => 'EngagementID',
            'label'      => 'Title',
            'permission' => 'speaking',
            'list_page'  => 'admin/speaking-engagements.php',
            'edit_page'  => 'admin/speaking-edit.php',
            'order'      => 't.EventDate DESC, t.Title',
            'list'       => ['Title', 'Venue', 'EventDate', 'Status'],
            'empty'      => 'Add talks and events once they are confirmed.',
            'fields'     => [
                'Title'            => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 255, 'group' => 'Event'],
                'Slug'             => ['label' => 'Web address', 'type' => 'slug', 'auto_from' => 'Title', 'max' => 200, 'group' => 'Event', 'help' => 'Leave blank to create one from the title.'],
                'Organiser'        => ['label' => 'Organiser', 'type' => 'text', 'max' => 255, 'group' => 'Event'],
                'Venue'            => ['label' => 'Venue', 'type' => 'text', 'max' => 255, 'group' => 'Event'],
                'City'             => ['label' => 'City', 'type' => 'text', 'max' => 100, 'group' => 'Event'],
                'EventDate'        => ['label' => 'Date', 'type' => 'date', 'group' => 'Event'],
                'EventLink'        => ['label' => 'Event link', 'type' => 'url', 'max' => 500, 'group' => 'Event'],
                'Status'           => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'group' => 'Event', 'permission' => 'speaking.publish'],
                'Description'      => ['label' => 'Description', 'type' => 'textarea', 'max' => 50000, 'group' => 'Details'],
                'FeaturedImage'    => ['label' => 'Image path', 'type' => 'path', 'max' => 500, 'group' => 'Details'],
                'FeaturedImageAlt' => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Details'],
            ],
        ],

        'page_sections' => [
            'title'      => 'Page Sections',
            'table'      => 'PageSections',
            'key'        => 'SectionID',
            'permission' => 'page_sections',
            'fields'     => [
                'Eyebrow'             => ['label' => 'Small heading', 'type' => 'text', 'max' => 255, 'group' => 'Wording'],
                'Heading'             => ['label' => 'Heading', 'type' => 'text', 'max' => 255, 'group' => 'Wording'],
                'Introduction'        => ['label' => 'Introduction', 'type' => 'textarea', 'max' => 5000, 'group' => 'Wording'],
                'Body'                => ['label' => 'Further text', 'type' => 'textarea', 'max' => 50000, 'group' => 'Wording'],
                'ButtonText'          => ['label' => 'Button text', 'type' => 'text', 'max' => 100, 'group' => 'Buttons'],
                'ButtonLink'          => ['label' => 'Button link', 'type' => 'link', 'max' => 500, 'group' => 'Buttons'],
                'SecondaryButtonText' => ['label' => 'Second button text', 'type' => 'text', 'max' => 100, 'group' => 'Buttons'],
                'SecondaryButtonLink' => ['label' => 'Second button link', 'type' => 'link', 'max' => 500, 'group' => 'Buttons'],
                'Image'               => ['label' => 'Image path', 'type' => 'path', 'max' => 500, 'group' => 'Image'],
                'ImageAlt'            => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Image'],
                'Status'              => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'group' => 'Visibility', 'permission' => 'page_sections.publish', 'help' => 'Only Published sections appear on the website.'],
            ],
        ],

        'page_section_items' => [
            'title'      => 'Item',
            'table'      => 'PageSectionItems',
            'key'        => 'ItemID',
            'permission' => 'page_sections',
            'fields'     => [
                'Title'        => ['label' => 'Title', 'type' => 'text', 'max' => 255],
                'Text'         => ['label' => 'Text', 'type' => 'textarea', 'max' => 5000],
                'Icon'         => ['label' => 'Icon', 'type' => 'select', 'options' => cms_icon_options()],
                'Link'         => ['label' => 'Link', 'type' => 'link', 'max' => 500],
                'Image'        => ['label' => 'Image path', 'type' => 'path', 'max' => 500],
                'ImageAlt'     => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'       => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'page_sections.publish'],
            ],
        ],
    ];
}

function cms_icon_options(): array
{
    return ['leaf', 'palette', 'camera', 'mic', 'group', 'chat', 'sprout', 'brush', 'flask', 'eye', 'pen', 'person', 'shield', 'mail', 'check'];
}

function cms_page_label(string $page): string
{
    return ['home' => 'Home', 'about' => 'About', 'services' => 'Services', 'global' => 'All pages'][$page] ?? ucfirst($page);
}

function cms_section_label(string $section): string
{
    return ['seo' => 'Search engines'][$section] ?? ucfirst(str_replace(['-', ' cta'], [' ', ' call to action'], str_replace('-cta', ' cta', $section)));
}
