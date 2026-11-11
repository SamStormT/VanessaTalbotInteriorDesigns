<?php
/**
 * Field definitions for the CMS framework.
 *
 * A new editable module is one more entry here: includes/cms.php handles the
 * view, form, validation, saving and audit trail. Table and field names come
 * only from this file, never from user input.
 *
 * Field types: text, textarea, lines, email, tel, url, link, image, slug, int,
 * select, checkbox, date. Optional keys: label, group, required, max, help,
 * options, option_labels, nullable, auto_from (build a slug from another field), permission
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
                'EnquiryEmail'    => ['label' => 'Send contact form messages to', 'type' => 'email', 'max' => 254, 'group' => 'Contact', 'help' => 'Not shown on the website. Leave blank to use the email above.'],
                'AddressLine1'    => ['label' => 'Street address', 'type' => 'text', 'max' => 150, 'group' => 'Location', 'help' => 'Only shown if the setting below is switched on.'],
                'AddressLine2'    => ['label' => 'Address line 2', 'type' => 'text', 'max' => 150, 'group' => 'Location'],
                'City'            => ['label' => 'City', 'type' => 'text', 'max' => 100, 'group' => 'Location'],
                'Province'        => ['label' => 'Province', 'type' => 'text', 'max' => 100, 'group' => 'Location'],
                'PostalCode'      => ['label' => 'Postal code', 'type' => 'text', 'max' => 10, 'group' => 'Location'],
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
                'HeroImage'            => ['label' => 'Hero image', 'type' => 'image', 'alt_field' => 'HeroImageAlt', 'max' => 500, 'group' => 'Hero'],
                'HeroImageAlt'         => ['label' => 'Hero image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Hero'],
                'CTA1Text'             => ['label' => 'First button text', 'type' => 'text', 'max' => 100, 'group' => 'Hero buttons'],
                'CTA1Link'             => ['label' => 'First button link', 'type' => 'link', 'max' => 500, 'group' => 'Hero buttons'],
                'CTA2Text'             => ['label' => 'Second button text', 'type' => 'text', 'max' => 100, 'group' => 'Hero buttons'],
                'CTA2Link'             => ['label' => 'Second button link', 'type' => 'link', 'max' => 500, 'group' => 'Hero buttons'],
                'FeaturedCTATitle'     => ['label' => 'Featured box title', 'type' => 'text', 'max' => 255, 'group' => 'Featured call to action'],
                'FeaturedCTAText'      => ['label' => 'Featured box text', 'type' => 'textarea', 'max' => 5000, 'group' => 'Featured call to action'],
                'FeaturedCTALink'      => ['label' => 'Featured box link', 'type' => 'link', 'max' => 500, 'group' => 'Featured call to action'],
                'IntroductionImage'    => ['label' => 'Introduction image', 'type' => 'image', 'alt_field' => 'IntroductionImageAlt', 'max' => 500, 'group' => 'Introduction'],
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
                'PortraitImage'        => ['label' => 'Portrait image', 'type' => 'image', 'alt_field' => 'PortraitImageAlt', 'max' => 500, 'group' => "Vanessa's story"],
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
                'FeaturedImage'    => ['label' => 'Image', 'type' => 'image', 'alt_field' => 'FeaturedImageAlt', 'max' => 500, 'group' => 'Image'],
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
                'FeaturedImage'    => ['label' => 'Image', 'type' => 'image', 'alt_field' => 'FeaturedImageAlt', 'max' => 500, 'group' => 'Details'],
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
                // Only shown on sections that display an image (cms_section_has_image()).
                'Image'               => ['label' => 'Image', 'type' => 'image', 'alt_field' => 'ImageAlt', 'max' => 500, 'group' => 'Image'],
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
                'Image'        => ['label' => 'Image', 'type' => 'image', 'alt_field' => 'ImageAlt', 'max' => 500],
                'ImageAlt'     => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'       => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'page_sections.publish'],
            ],
        ],

        'portfolio' => [
            'title'      => 'Portfolio Projects',
            'table'      => 'PortfolioProjects',
            'key'        => 'ProjectID',
            'label'      => 'Title',
            'permission' => 'portfolio',
            'list_page'  => 'admin/portfolio-projects.php',
            'edit_page'  => 'admin/project-edit.php',
            'order'      => 't.DisplayOrder, t.Title',
            'list'       => ['Title', 'PortfolioCategory', 'ProjectType', 'Status'],
            'empty'      => 'Add a project once the client has agreed to it being shown.',
            'publish_requires' => ['ClientPermission', 'Tick this to confirm the client has agreed before publishing.'],
            'fields'     => [
                'Title'             => ['label' => 'Project title', 'type' => 'text', 'required' => true, 'max' => 255, 'group' => 'Project'],
                'Slug'              => ['label' => 'Web address', 'type' => 'slug', 'auto_from' => 'Title', 'max' => 200, 'group' => 'Project', 'help' => 'Leave blank to create one from the title.'],
                'PortfolioCategory' => ['label' => 'Portfolio page', 'type' => 'select', 'required' => true, 'options' => ['Biophilic Interior Design', 'Colour Psychology', 'Photography Gallery'], 'option_labels' => ['Colour Psychology' => 'Colour & Design Psychology'], 'group' => 'Project'],
                'ProjectType'       => ['label' => 'Project type', 'type' => 'select', 'required' => true, 'options' => ['Residential', 'Commercial', 'Concept', 'Personal', 'Photography'], 'group' => 'Project'],
                'Location'          => ['label' => 'Location', 'type' => 'text', 'max' => 150, 'group' => 'Project', 'help' => 'Leave blank if the client prefers not to show it.'],
                'Description'       => ['label' => 'Description', 'type' => 'textarea', 'max' => 50000, 'group' => 'Project'],
                'FeaturedImage'     => ['label' => 'Main image', 'type' => 'image', 'alt_field' => 'FeaturedImageAlt', 'max' => 500, 'group' => 'Main image'],
                'FeaturedImageAlt'  => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255, 'group' => 'Main image'],
                'ClientPermission'  => ['label' => 'The client has agreed to this project being shown online', 'type' => 'checkbox', 'group' => 'Publishing'],
                'IsFeatured'        => ['label' => 'Feature on the Portfolio page', 'type' => 'checkbox', 'group' => 'Publishing'],
                'DisplayOrder'      => ['label' => 'Display order', 'type' => 'int', 'group' => 'Publishing', 'help' => 'Lower numbers appear first.'],
                'Status'            => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'group' => 'Publishing', 'permission' => 'portfolio.publish'],
            ] + $meta,
        ],

        'project_images' => [
            'title'      => 'Project Image',
            'table'      => 'ProjectImages',
            'key'        => 'ImageID',
            'permission' => 'portfolio',
            'fields'     => [
                'ImagePath'    => ['label' => 'Image', 'type' => 'image', 'alt_field' => 'AltText', 'caption_field' => 'Caption', 'required' => true, 'max' => 500],
                'AltText'      => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255],
                'Caption'      => ['label' => 'Caption', 'type' => 'text', 'max' => 500],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
            ],
        ],

        'collaborators' => [
            'title'      => 'Collaborators',
            'table'      => 'Collaborators',
            'key'        => 'CollaboratorID',
            'label'      => 'Name',
            'permission' => 'collaborators',
            'list_page'  => 'admin/collaborators.php',
            'edit_page'  => 'admin/collaborator-edit.php',
            'order'      => 't.DisplayOrder, t.Name',
            'list'       => ['Name', 'RoleTitle', 'ConsentDate', 'Status'],
            'empty'      => 'Add people and businesses once they have agreed to be listed.',
            'publish_requires' => ['ConsentDate', 'Enter the date they agreed to be listed before publishing.'],
            'fields'     => [
                'Name'         => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 200],
                'Slug'         => ['label' => 'Web address', 'type' => 'slug', 'auto_from' => 'Name', 'max' => 200, 'help' => 'Leave blank to create one from the name.'],
                'RoleTitle'    => ['label' => 'What they do', 'type' => 'text', 'max' => 150],
                'Description'  => ['label' => 'Description', 'type' => 'textarea', 'max' => 5000],
                'Logo'         => ['label' => 'Logo or photo', 'type' => 'image', 'alt_field' => 'LogoAlt', 'max' => 500],
                'LogoAlt'      => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255],
                'WebsiteLink'  => ['label' => 'Website', 'type' => 'url', 'max' => 500],
                'ConsentDate'  => ['label' => 'Date they agreed to be listed', 'type' => 'date'],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'       => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'collaborators.publish'],
            ],
        ],

        'site_pages' => [
            'title'      => 'Legal Pages',
            'table'      => 'SitePages',
            'key'        => 'PageID',
            'label'      => 'Title',
            'permission' => 'site_pages',
            'list_page'  => 'admin/site-pages.php',
            'edit_page'  => 'admin/site-page-edit.php',
            'order'      => 't.Title',
            'list'       => ['Title', 'Status'],
            'fields'     => [
                'Title'           => ['label' => 'Page title', 'type' => 'text', 'required' => true, 'max' => 255, 'group' => 'Page'],
                'Content'         => ['label' => 'Page wording', 'type' => 'textarea', 'max' => 200000, 'group' => 'Page', 'help' => 'Leave a blank line between paragraphs. Start a line with ## for a heading, and with - for a list item.'],
                'Status'          => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'group' => 'Page', 'permission' => 'site_pages.publish', 'help' => 'Only Published pages appear on the website.'],
                'MetaTitle'       => ['label' => 'Search result title', 'type' => 'text', 'max' => 255, 'group' => 'Search engines'],
                'MetaDescription' => ['label' => 'Search result description', 'type' => 'textarea', 'max' => 500, 'group' => 'Search engines'],
            ],
        ],

        'testimonials' => [
            'title'      => 'Testimonials',
            'table'      => 'Testimonials',
            'key'        => 'TestimonialID',
            'label'      => 'Name',
            'permission' => 'testimonials',
            'list_page'  => 'admin/testimonials.php',
            'edit_page'  => 'admin/testimonial-edit.php',
            'order'      => 't.DisplayOrder, t.Name',
            'list'       => ['Name', 'Company', 'ConsentDate', 'Status'],
            'empty'      => 'Add testimonials once the client has given written permission.',
            'publish_requires' => ['ConsentDate', 'Enter the date the client gave written permission before publishing.'],
            'fields'     => [
                'Name'         => ['label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                'Company'      => ['label' => 'Role or company', 'type' => 'text', 'max' => 150],
                'Location'     => ['label' => 'Town or city', 'type' => 'text', 'max' => 150],
                'Testimonial'  => ['label' => 'Testimonial', 'type' => 'textarea', 'required' => true, 'max' => 5000],
                'Category'     => ['label' => 'Service it relates to', 'type' => 'text', 'max' => 100],
                'Photo'        => ['label' => 'Photo or logo', 'type' => 'image', 'alt_field' => 'PhotoAlt', 'max' => 500],
                'PhotoAlt'     => ['label' => 'Image description (alt text)', 'type' => 'text', 'max' => 255],
                'ConsentDate'  => ['label' => 'Date written permission was given', 'type' => 'date'],
                'IsFeatured'   => ['label' => 'Feature this testimonial', 'type' => 'checkbox'],
                'DisplayOrder' => ['label' => 'Display order', 'type' => 'int', 'help' => 'Lower numbers appear first.'],
                'Status'       => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['Draft', 'Published', 'Archived'], 'permission' => 'testimonials.publish'],
            ],
        ],

        'media' => [
            'title'      => 'Media Library',
            'table'      => 'MediaLibrary',
            'key'        => 'MediaID',
            'permission' => 'media',
            'fields'     => [
                'AltText'  => ['label' => 'Description (alt text)', 'type' => 'text', 'max' => 255, 'help' => 'Describes the image for people who cannot see it. Reused wherever this image is chosen.'],
                'Caption'  => ['label' => 'Caption', 'type' => 'text', 'max' => 500],
                'Category' => ['label' => 'Category', 'type' => 'text', 'max' => 100],
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
    return [
        'home' => 'Home', 'about' => 'About', 'services' => 'Services', 'global' => 'All pages', 'contact' => 'Contact', 'portfolio' => 'Portfolio',
        'service-biophilic-design' => 'Service: Biophilic Design',
        'service-colour-psychology' => 'Service: Colour & Design Psychology',
        'service-photography-services' => 'Service: Photography',
        'service-workshops' => 'Service: Workshops',
        'service-speaking-engagements' => 'Service: Speaking Engagements',
        'service-consultations' => 'Service: Consultations',
        'portfolio-biophilic-interior-design' => 'Portfolio: Biophilic Interior Design',
        'portfolio-colour-psychology' => 'Portfolio: Colour & Design Psychology',
        'portfolio-photography-gallery' => 'Portfolio: Photography',
        'portfolio-collaborators' => 'Portfolio: Collaborators',
    ][$page] ?? ucfirst($page);
}

/**
 * Sections whose image appears on the website: every page header (the photo
 * replaces the botanical drawing) and the About page's speaking block (it
 * replaces the microphone).
 */
function cms_section_has_image(string $page, string $section): bool
{
    return $section === 'page-header' || ($page === 'about' && $section === 'speaking');
}

/** The section editor's fields: the image fields only where the image is shown. */
function cms_section_module(string $page, string $section): array
{
    $module = cms_module('page_sections');
    if (!cms_section_has_image($page, $section)) {
        unset($module['fields']['Image'], $module['fields']['ImageAlt']);
    } elseif ($section === 'page-header') {
        $module['fields']['Image']['help'] = 'Shown behind the page heading, softened so the wording stays easy to read. Without an image, the botanical drawing is shown. A wide landscape photo works best.';
    } else {
        $module['fields']['Image']['help'] = 'Shown beside the speaking wording. Without an image, a microphone illustration is shown.';
    }
    return $module;
}

function cms_section_label(string $section): string
{
    return ['seo' => 'Search engines'][$section] ?? ucfirst(str_replace(['-', ' cta'], [' ', ' call to action'], str_replace('-cta', ' cta', $section)));
}
