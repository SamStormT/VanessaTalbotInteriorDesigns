<?php
/**
 * Shared site content used on more than one page.
 *
 * Kept in one place so service names, descriptions and links stay consistent
 * between the home page, services page and navigation. In a later phase this
 * can move to the database and be managed from the admin area.
 */

declare(strict_types=1);

/** Who Vanessa works with. */
$targetMarkets = [
    'Residential homes',
    'Workspaces',
    'Health & wellness centres',
    'Boutiques',
    'Events',
];

/** Core brand values. */
$coreValues = [
    [
        'title' => 'Authenticity',
        'icon'  => 'sprout',
        'text'  => 'Spaces that reflect who you really are, not what a trend says they should be.',
    ],
    [
        'title' => 'Creativity',
        'icon'  => 'brush',
        'text'  => 'Fresh, considered ideas that bring art, colour and natural form together in unexpected ways.',
    ],
    [
        'title' => 'Individuality',
        'icon'  => 'person',
        'text'  => 'Every person, business and building is different, so every design begins with listening.',
    ],
    [
        'title' => 'Honesty',
        'icon'  => 'eye',
        'text'  => 'Clear advice, realistic expectations and straightforward communication at every stage.',
    ],
    [
        'title' => 'Integrity',
        'icon'  => 'shield',
        'text'  => 'Recommendations grounded in evidence and made with care for people and the natural world.',
    ],
];

/** Services offered. Used on the home page cards and the services page sections. */
$services = [
    [
        'slug'     => 'biophilic-design',
        'title'    => 'Biophilic Design',
        'icon'     => 'leaf',
        'url'      => 'services/biophilic-design.php',
        'summary'  => 'Interiors that reconnect people with nature through light, living plants, natural materials and organic form.',
        'lead'     => 'People are wired to feel better around nature. Biophilic design brings that connection indoors, deliberately and beautifully.',
        'body'     => 'Drawing on research into how natural light, greenery, texture, water and views affect the way we feel and function, Vanessa designs spaces that calm the mind, lift energy and support everyday wellbeing. The result is an interior that feels alive, grounded and quietly restorative.',
        'includes' => [
            'Assessment of light, airflow, views and how the space is used',
            'Natural materials, textures and organic shapes',
            'Planting schemes suited to the space and the people who care for it',
            'Layouts that support rest, focus and connection',
        ],
        'ideal'    => ['Residential homes', 'Workspaces', 'Health & wellness centres'],
    ],
    [
        'slug'     => 'colour-psychology',
        'title'    => 'Colour Psychology',
        'icon'     => 'palette',
        'url'      => 'services/colour-psychology.php',
        'summary'  => 'Colour chosen with purpose, to influence mood, energy and behaviour in the way each space needs.',
        'lead'     => 'Colour is never just decoration. It shapes how a room feels, and how we feel in it.',
        'body'     => 'Vanessa combines an understanding of how colour affects mood and perception with a designer\'s eye for harmony and balance. Whether a space needs to soothe, energise, welcome or inspire, the palette is built around the people who use it and what they need it to do.',
        'includes' => [
            'Colour consultation based on how the space is used and who uses it',
            'Considered palettes for walls, furnishings and finishes',
            'Guidance on natural and artificial light and how it changes colour',
            'Brand-aligned colour for commercial and retail spaces',
        ],
        'ideal'    => ['Residential homes', 'Boutiques', 'Workspaces'],
    ],
    [
        'slug'     => 'photography-services',
        'title'    => 'Photography',
        'icon'     => 'camera',
        'url'      => 'services/photography-services.php',
        'summary'  => 'Nature and interior photography that captures texture, light and atmosphere.',
        'lead'     => 'Photography is where Vanessa\'s eye for detail and love of the natural world meet.',
        'body'     => 'From botanical detail and natural landscapes to finished interiors, Vanessa\'s photography is about noticing: the way light falls, the pattern in a leaf, the mood of a room. Selected images are available as prints through the photography store, bringing a piece of nature into your own space.',
        'includes' => [
            'Nature and botanical fine-art photography',
            'Photography prints available through the online store',
            'Interior photography that shows a space at its best',
            'Imagery for designers, boutiques and wellness brands',
        ],
        'ideal'    => ['Residential homes', 'Boutiques', 'Health & wellness centres'],
    ],
    [
        'slug'     => 'speaking-engagements',
        'title'    => 'Speaking Engagements',
        'icon'     => 'mic',
        'url'      => 'services/speaking-engagements.php',
        'summary'  => 'Engaging talks on biophilic design, colour psychology and nature-centred wellbeing.',
        'lead'     => 'Ideas that change how audiences see the spaces they live and work in.',
        'body'     => 'Vanessa speaks on the science and the art of designing with nature: why our surroundings matter so much to our wellbeing, and what anyone can do about it. Talks are tailored to the audience, whether a conference, a corporate wellness day, a community group or an industry event.',
        'includes' => [
            'Keynotes and talks tailored to your audience',
            'Topics spanning biophilic design, colour and wellbeing',
            'Practical takeaways audiences can use straight away',
            'Panel discussions and question-and-answer sessions',
        ],
        'ideal'    => ['Events', 'Workspaces', 'Health & wellness centres'],
    ],
    [
        'slug'     => 'workshops',
        'title'    => 'Workshops',
        'icon'     => 'group',
        'url'      => 'services/workshops.php',
        'summary'  => 'Hands-on, interactive sessions that teach people to bring nature and colour into their own spaces.',
        'lead'     => 'Learn by doing, and leave with ideas you can put into practice at home or at work.',
        'body'     => 'Vanessa\'s workshops turn the principles of biophilic design and colour psychology into practical, enjoyable experiences. Participants explore how their surroundings affect them, then work through simple, achievable changes. Sessions suit teams, wellness programmes, retail events and community groups.',
        'includes' => [
            'Interactive group sessions with hands-on activities',
            'Practical guides and worksheets to take home',
            'Content tailored to teams, clients or communities',
            'Options for corporate wellness and team-building days',
        ],
        'ideal'    => ['Workspaces', 'Events', 'Health & wellness centres'],
    ],
    [
        'slug'     => 'consultations',
        'title'    => 'Consultations',
        'icon'     => 'chat',
        'url'      => 'services/consultations.php',
        'summary'  => 'One-on-one guidance to help you understand your space and plan meaningful change.',
        'lead'     => 'Not every space needs a full redesign. Sometimes the right advice changes everything.',
        'body'     => 'A consultation is a focused session to look at your space together, understand how you use it and how you want it to feel, and agree on clear next steps. It is the ideal starting point for any project, and can stand alone for clients who want expert direction to carry out themselves.',
        'includes' => [
            'A walk-through and discussion of your space and goals',
            'Biophilic and colour recommendations specific to you',
            'Clear, prioritised next steps',
            'A starting point for a larger design project, if you choose',
        ],
        'ideal'    => ['Residential homes', 'Workspaces', 'Boutiques', 'Health & wellness centres'],
    ],
];

/** Portfolio categories featured on the home page. */
$portfolioCategories = [
    [
        'title' => 'Biophilic Interior Design',
        'text'  => 'Homes and workplaces brought to life with natural light, planting and organic materials.',
        'url'   => 'portfolio/biophilic-interior-design.php',
        'icon'  => 'leaf',
        'tone'  => 'green',
    ],
    [
        'title' => 'Colour Psychology',
        'text'  => 'Palettes designed to calm, energise or welcome, depending on what the space needs.',
        'url'   => 'portfolio/colour-psychology.php',
        'icon'  => 'palette',
        'tone'  => 'gold',
    ],
    [
        'title' => 'Photography Gallery',
        'text'  => 'Nature, botanical detail and interiors seen through Vanessa\'s lens.',
        'url'   => 'portfolio/photography-gallery.php',
        'icon'  => 'camera',
        'tone'  => 'peach',
    ],
];
