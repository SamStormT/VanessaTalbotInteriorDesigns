-- ==========================================================================
-- Vanessa Talbot Interior Designs: database migrations
-- Run after schema.sql. Safe to re-run: structure changes are additive and
-- each content seed runs once only, so it can never restore edited content.
-- ==========================================================================

SET NAMES utf8mb4;

-- --------------------------------------------------------------------------
-- 002: LastEditedBy and LastEditedDate on every editable content table.
-- If the staff member is ever removed, the date stays and the name clears.
-- --------------------------------------------------------------------------
ALTER TABLE WebsiteSettings
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_WebsiteSettings_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_WebsiteSettings_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE HomePageContent
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_HomePageContent_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_HomePageContent_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE AboutContent
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_AboutContent_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_AboutContent_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE AboutValues
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_AboutValues_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_AboutValues_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE AboutQualifications
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_AboutQualifications_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_AboutQualifications_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE SitePages
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_SitePages_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_SitePages_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE Services
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_Services_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_Services_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE PortfolioProjects
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_PortfolioProjects_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_PortfolioProjects_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE Collaborators
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_Collaborators_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_Collaborators_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE Testimonials
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_Testimonials_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_Testimonials_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE SpeakingEngagements
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_SpeakingEngagements_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_SpeakingEngagements_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE PhotographyProducts
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_PhotographyProducts_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_PhotographyProducts_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE NewsletterCampaigns
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE BlogPosts
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_BlogPosts_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_BlogPosts_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('002_last_edited_columns');

-- --------------------------------------------------------------------------
-- 003: Website Settings location defaults. Runs once; keeps any saved value.
-- Phone and email stay out of this public repository: database/setup.php
-- loads them from the private .env file instead.
-- --------------------------------------------------------------------------
UPDATE WebsiteSettings
SET City = COALESCE(City, 'Pietermaritzburg'),
    Province = COALESCE(Province, 'KwaZulu-Natal'),
    Country = COALESCE(Country, 'South Africa')
WHERE SettingID = 1 AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '003_settings_location_defaults');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('003_settings_location_defaults');

-- --------------------------------------------------------------------------
-- 004: The six existing services, with the wording already on the site.
-- Runs once only, so a service that is later renamed is never added again.
-- --------------------------------------------------------------------------
INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Biophilic Design', 'biophilic-design', 'leaf', 'Interiors that reconnect people with nature through light, living plants, natural materials and organic form.', 'People are wired to feel better around nature. Biophilic design brings that connection indoors, deliberately and beautifully.', 'Drawing on research into how natural light, greenery, texture, water and views affect the way we feel and function, Vanessa designs spaces that calm the mind, lift energy and support everyday wellbeing. The result is an interior that feels alive, grounded and quietly restorative.', 'Assessment of light, airflow, views and how the space is used\nNatural materials, textures and organic shapes\nPlanting schemes suited to the space and the people who care for it\nLayouts that support rest, focus and connection', 'Residential homes\nWorkspaces\nHealth & wellness centres', 'Published', 10
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Colour & Design Psychology', 'colour-psychology', 'palette', 'Colour chosen with purpose, to influence mood, energy and behaviour in the way each space needs.', 'Colour is never just decoration. It shapes how a room feels, and how we feel in it.', 'Vanessa combines an understanding of how colour affects mood and perception with a designer''s eye for harmony and balance. Whether a space needs to soothe, energise, welcome or inspire, the palette is built around the people who use it and what they need it to do.', 'Colour consultation based on how the space is used and who uses it\nConsidered palettes for walls, furnishings and finishes\nGuidance on natural and artificial light and how it changes colour\nBrand-aligned colour for commercial and retail spaces', 'Residential homes\nBoutiques\nWorkspaces', 'Published', 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Photography', 'photography-services', 'camera', 'Nature and interior photography that captures texture, light and atmosphere.', 'Photography is where Vanessa''s eye for detail and love of the natural world meet.', 'From botanical detail and natural landscapes to finished interiors, Vanessa''s photography is about noticing: the way light falls, the pattern in a leaf, the mood of a room. Selected images are available as prints through the photography store, bringing a piece of nature into your own space.', 'Nature and botanical fine-art photography\nPhotography prints available through the online store\nInterior photography that shows a space at its best\nImagery for designers, boutiques and wellness brands', 'Residential homes\nBoutiques\nHealth & wellness centres', 'Published', 30
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Speaking Engagements', 'speaking-engagements', 'mic', 'Engaging talks on biophilic design, colour & design psychology and nature-centred wellbeing.', 'Ideas that change how audiences see the spaces they live and work in.', 'Vanessa speaks on the science and the art of designing with nature: why our surroundings matter so much to our wellbeing, and what anyone can do about it. Talks are tailored to the audience, whether a conference, a corporate wellness day, a community group or an industry event.', 'Keynotes and talks tailored to your audience\nTopics spanning biophilic design, colour and wellbeing\nPractical takeaways audiences can use straight away\nPanel discussions and question-and-answer sessions', 'Events\nWorkspaces\nHealth & wellness centres', 'Published', 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Workshops', 'workshops', 'group', 'Hands-on, interactive sessions that teach people to bring nature and colour into their own spaces.', 'Learn by doing, and leave with ideas you can put into practice at home or at work.', 'Vanessa''s workshops turn the principles of biophilic design and colour & design psychology into practical, enjoyable experiences. Participants explore how their surroundings affect them, then work through simple, achievable changes. Sessions suit teams, wellness programmes, retail events and community groups.', 'Interactive group sessions with hands-on activities\nPractical guides and worksheets to take home\nContent tailored to teams, clients or communities\nOptions for corporate wellness and team-building days', 'Workspaces\nEvents\nHealth & wellness centres', 'Published', 50
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Consultations', 'consultations', 'chat', 'One-on-one guidance to help you understand your space and plan meaningful change.', 'Not every space needs a full redesign. Sometimes the right advice changes everything.', 'A consultation is a focused session to look at your space together, understand how you use it and how you want it to feel, and agree on clear next steps. It is the ideal starting point for any project, and can stand alone for clients who want expert direction to carry out themselves.', 'A walk-through and discussion of your space and goals\nBiophilic and colour recommendations specific to you\nClear, prioritised next steps\nA starting point for a larger design project, if you choose', 'Residential homes\nWorkspaces\nBoutiques\nHealth & wellness centres', 'Published', 60
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('004_seed_services');

-- --------------------------------------------------------------------------
-- 005: Home and About wording currently shown on the public pages, so it can
-- be reviewed in the CMS. Runs once and only fills empty fields.
-- See CONTENT_MAPPING.md for where each piece of text belongs.
-- --------------------------------------------------------------------------
UPDATE HomePageContent
SET     HeroEyebrow = COALESCE(HeroEyebrow, 'Biophilic Interior Design · Colour & Design Psychology · Photography'),
    HeroTitle = COALESCE(HeroTitle, 'Vanessa Talbot'),
    HeroSubtitle = COALESCE(HeroSubtitle, 'Interiors designed around the way people are meant to live: connected to light, colour and the natural world. Grounded in science, shaped by art, inspired by nature.'),
    CTA1Text = COALESCE(CTA1Text, 'Book a consultation'),
    CTA1Link = COALESCE(CTA1Link, 'services/consultations.php'),
    CTA2Text = COALESCE(CTA2Text, 'Explore services'),
    CTA2Link = COALESCE(CTA2Link, 'services.php'),
    FeaturedCTATitle = COALESCE(FeaturedCTATitle, 'Bring nature back into your space'),
    FeaturedCTAText = COALESCE(FeaturedCTAText, 'Start with a one-on-one consultation. Together we''ll look at how your space feels, how you use it, and the simple, meaningful changes that will make the biggest difference.'),
    FeaturedCTALink = COALESCE(FeaturedCTALink, 'services/consultations.php'),
    IntroductionText = COALESCE(IntroductionText, 'Vanessa Talbot brings together a background in science and a passion for interior design to create spaces that do more than look beautiful. They help people feel well.\n\nHer work is built on a simple idea: the spaces we spend our days in shape how we think, feel and connect. By combining biophilic design, the psychology of colour and a photographer''s eye for light and detail, Vanessa designs interiors that are calm, personal and deeply connected to the natural world.'),
    FeaturedQuote = COALESCE(FeaturedQuote, 'Nature remains our magic well: the more we draw from its nourishing waters, the more we sustain the human body, mind and spirit.'),
    FeaturedQuoteAuthor = COALESCE(FeaturedQuoteAuthor, 'Stephen R. Kellert'),
    MetaDescription = COALESCE(MetaDescription, 'Vanessa Talbot Interior Designs creates nature-centred interiors through biophilic design, colour & design psychology and photography, for homes, workspaces, wellness centres, boutiques and events.')
WHERE HomePageID = 1 AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

UPDATE AboutContent
SET     Biography = COALESCE(Biography, 'Vanessa Talbot has always been fascinated by the relationship between people and the places they spend their lives in.\n\nIt is a curiosity that has carried her from science to interior design, and into photography. Each one has taught her to look a little more closely, at how light moves through a room, how colour changes a mood, and how a single living plant can soften an entire space.\n\nToday, Vanessa Talbot Interior Designs brings those threads together. Vanessa works with homeowners, businesses, wellness practitioners, retailers and event organisers to create interiors that are beautiful, personal and good for the people in them.'),
    Journey = COALESCE(Journey, 'Vanessa''s path into design is what makes her approach different. She doesn''t see science and creativity as opposites. To her, they are two ways of understanding the same world.\n\nBiophilic design and colour & design psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.'),
    ScienceBackground = COALESCE(ScienceBackground, 'A background in science taught Vanessa to observe carefully, ask questions and look for evidence. It also gave her a lasting respect for the natural systems that sustain us.'),
    DesignBackground = COALESCE(DesignBackground, 'Her love of beauty, colour and composition drew her to interior design, where she discovered she could use both sides of her thinking at once.'),
    Philosophy = COALESCE(Philosophy, 'Biophilia is our innate need to connect with nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.\n\nIn practice, that means designing with:\n\nLight and air: making the most of daylight, fresh air and views to the outside.\nLiving elements: plants, water and natural features that bring a space to life.\nNatural materials: wood, stone, linen and texture that invite touch and feel grounded.\nColour with purpose: palettes drawn from nature and chosen for how they make people feel.\nOrganic form: shapes, patterns and layouts that echo the natural world.'),
    Qualifications = COALESCE(Qualifications, 'Vanessa''s practice is built on her background in science and interior design, and an ongoing commitment to learning.'),
    SpeakingIntroduction = COALESCE(SpeakingIntroduction, 'Vanessa speaks to audiences about why our surroundings matter so much, and how anyone can bring more nature, colour and calm into the places they live and work.\n\nTalks are tailored to each audience. Popular themes include:'),
    SpeakingTopics = COALESCE(SpeakingTopics, 'Why nature belongs indoors: the case for biophilic design\nThe psychology of colour in everyday spaces\nDesigning workspaces that support focus and wellbeing\nSeeing like a photographer: light, texture and detail at home'),
    MetaTitle = COALESCE(MetaTitle, 'About Vanessa'),
    MetaDescription = COALESCE(MetaDescription, 'Meet Vanessa Talbot: an interior designer with a background in science, creating biophilic, colour-led spaces that support wellbeing and reconnect people with nature.')
WHERE AboutID = 1 AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Authenticity', 'Spaces that reflect who you really are, not what a trend says they should be.', 'sprout', 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Creativity', 'Fresh, considered ideas that bring art, colour and natural form together in unexpected ways.', 'brush', 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Individuality', 'Every person, business and building is different, so every design begins with listening.', 'person', 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Honesty', 'Clear advice, realistic expectations and straightforward communication at every stage.', 'eye', 40, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Integrity', 'Recommendations grounded in evidence and made with care for people and the natural world.', 'shield', 50, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('005_seed_home_about_content');

-- --------------------------------------------------------------------------
-- 006: PageSections and PageSectionItems.
-- Headings, introductions, buttons and repeating blocks for any page.
-- PageKey and SectionKey are plain text, so a new page or section only ever
-- needs new rows, never a structure change. Suggested PageKey values: home,
-- about, services, portfolio, photography, speaking, testimonials, contact,
-- legal, global.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PageSections (
    SectionID           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PageKey             VARCHAR(50)  NOT NULL,
    SectionKey          VARCHAR(100) NOT NULL,
    Eyebrow             VARCHAR(255) NULL,
    Heading             VARCHAR(255) NULL,
    Introduction        TEXT NULL,
    Body                MEDIUMTEXT NULL,
    ButtonText          VARCHAR(100) NULL,
    ButtonLink          VARCHAR(500) NULL,
    SecondaryButtonText VARCHAR(100) NULL,
    SecondaryButtonLink VARCHAR(500) NULL,
    Image               VARCHAR(500) NULL,
    ImageAlt            VARCHAR(255) NULL,
    DisplayOrder        INT NOT NULL DEFAULT 0,
    Status              ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LastEditedBy        INT UNSIGNED NULL,
    LastEditedDate      DATETIME NULL,
    PRIMARY KEY (SectionID),
    UNIQUE KEY uq_PageSections_Key (PageKey, SectionKey),
    KEY idx_PageSections_Listing (PageKey, Status, DisplayOrder),
    CONSTRAINT fk_PageSections_LastEditedBy FOREIGN KEY (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PageSectionItems (
    ItemID         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    SectionID      INT UNSIGNED NOT NULL,
    ItemKey        VARCHAR(100) NULL,
    Title          VARCHAR(255) NULL,
    Text           TEXT NULL,
    Icon           VARCHAR(50)  NULL,
    Link           VARCHAR(500) NULL,
    Image          VARCHAR(500) NULL,
    ImageAlt       VARCHAR(255) NULL,
    DisplayOrder   INT NOT NULL DEFAULT 0,
    Status         ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LastEditedBy   INT UNSIGNED NULL,
    LastEditedDate DATETIME NULL,
    PRIMARY KEY (ItemID),
    UNIQUE KEY uq_PageSectionItems_Key (SectionID, ItemKey),
    KEY idx_PageSectionItems_Listing (SectionID, Status, DisplayOrder),
    CONSTRAINT fk_PageSectionItems_Section FOREIGN KEY (SectionID)
        REFERENCES PageSections (SectionID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_PageSectionItems_LastEditedBy FOREIGN KEY (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'feature-cta', NULL, NULL, NULL, NULL, 'Start with a consultation', NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'introduction', 'Meet Vanessa', 'Where science, art and nature meet', 'Vanessa Talbot brings together a background in science and a passion for interior design to create spaces that do more than look beautiful. They help people feel well.', 'Her work is built on a simple idea: the spaces we spend our days in shape how we think, feel and connect. By combining biophilic design, the psychology of colour and a photographer''s eye for light and detail, Vanessa designs interiors that are calm, personal and deeply connected to the natural world.', 'Read Vanessa''s story', 'about.php', NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'art', 'Art', 'Beauty, harmony and creative expression', 'brush', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'introduction' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'science', 'Science', 'Evidence-informed design decisions', 'flask', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'introduction' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'nature', 'Nature', 'Living, restorative connection', 'sprout', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'introduction' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'services', 'Services', 'Designing with nature, in every sense', 'From a single consultation to a complete biophilic redesign, every service is shaped around the people who will use the space.', NULL, 'View all services', 'services.php', NULL, NULL, 50, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'portfolio', 'Portfolio', 'Spaces with a sense of place', 'Explore Vanessa''s work across biophilic interiors, colour-led design and photography.', NULL, 'View the full portfolio', 'portfolio.php', NULL, NULL, 60, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'biophilic-interior-design', 'Biophilic Interior Design', 'Homes and workplaces brought to life with natural light, planting and organic materials.', 'leaf', 'portfolio/biophilic-interior-design.php', 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'portfolio' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'colour-psychology', 'Colour & Design Psychology', 'Palettes designed to calm, energise or welcome, depending on what the space needs.', 'palette', 'portfolio/colour-psychology.php', 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'portfolio' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'photography-gallery', 'Photography Gallery', 'Nature, botanical detail and interiors seen through Vanessa''s lens.', 'camera', 'portfolio/photography-gallery.php', 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'portfolio' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'blog', 'From the blog', 'Ideas for living closer to nature', NULL, NULL, NULL, NULL, NULL, NULL, 70, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'blog-empty', NULL, 'The first articles are on their way', 'Vanessa will be sharing practical ideas on biophilic design, the psychology of colour, nature-centred wellbeing and the stories behind her photography. Join the newsletter below to be the first to read them.', NULL, NULL, NULL, NULL, NULL, 75, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'biophilic-design', 'Biophilic design', NULL, NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'blog-empty' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'colour-psychology', 'Colour & design psychology', NULL, NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'blog-empty' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'wellbeing-at-home', 'Wellbeing at home', NULL, NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'blog-empty' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'healthier-workspaces', 'Healthier workspaces', NULL, NULL, NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'blog-empty' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'photography', 'Photography', NULL, NULL, NULL, 50, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'blog-empty' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'newsletter', 'Newsletter', 'Nature-inspired ideas, delivered to your inbox', 'Seasonal inspiration, colour and design tips, new photography prints, and first news of upcoming workshops and talks. No clutter, just ideas worth your time.', 'Your details are only used to send the newsletter. See our Privacy Policy.', NULL, NULL, NULL, NULL, 80, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'contact-cta', NULL, 'Let''s create a space that feels like you', 'Whether it''s your home, your workspace, a wellness centre, a boutique or an event, Vanessa would love to hear what you have in mind.', NULL, 'Get in touch', 'contact.php', 'See the portfolio', 'portfolio.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'residential-homes', 'Residential homes', NULL, NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'contact-cta' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'workspaces', 'Workspaces', NULL, NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'contact-cta' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'health-wellness-centres', 'Health & wellness centres', NULL, NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'contact-cta' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'boutiques', 'Boutiques', NULL, NULL, NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'contact-cta' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'events', 'Events', NULL, NULL, NULL, 50, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'contact-cta' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'page-header', NULL, 'About Vanessa', NULL, NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'story', 'Vanessa''s story', 'Designing spaces that help people thrive', NULL, NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'journey', 'The journey', 'From science to interior design', 'Vanessa''s path into design is what makes her approach different. She doesn''t see science and creativity as opposites. To her, they are two ways of understanding the same world.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'science', 'A scientific foundation', 'A background in science taught Vanessa to observe carefully, ask questions and look for evidence. It also gave her a lasting respect for the natural systems that sustain us.', 'flask', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'design', 'A creative calling', 'Her love of beauty, colour and composition drew her to interior design, where she discovered she could use both sides of her thinking at once.', 'brush', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'nature', 'Nature as the bridge', 'Biophilic design and colour & design psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.', 'sprout', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'philosophy', 'Philosophy', 'A biophilic approach to design', 'Biophilia is our innate need to connect with nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.', 'In practice, that means designing with:', NULL, NULL, NULL, NULL, 40, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'light-and-air', 'Light and air', 'making the most of daylight, fresh air and views to the outside.', NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'philosophy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'living-elements', 'Living elements', 'plants, water and natural features that bring a space to life.', NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'philosophy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'natural-materials', 'Natural materials', 'wood, stone, linen and texture that invite touch and feel grounded.', NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'philosophy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'colour-with-purpose', 'Colour with purpose', 'palettes drawn from nature and chosen for how they make people feel.', NULL, NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'philosophy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'organic-form', 'Organic form', 'shapes, patterns and layouts that echo the natural world.', NULL, NULL, 50, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'philosophy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'quote', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 45, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'kellert', 'Stephen R. Kellert', 'Nature remains our magic well: the more we draw from its nourishing waters, the more we sustain the human body, mind and spirit.', NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'quote' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'values', 'Values', 'What guides every project', NULL, NULL, NULL, NULL, NULL, NULL, 50, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'qualifications', 'Qualifications', 'Training and expertise', NULL, NULL, NULL, NULL, NULL, NULL, 60, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'qualifications-notice', NULL, 'Full details coming soon', 'A complete list of Vanessa''s qualifications, professional memberships and accreditations will be published here shortly. For details in the meantime, please get in touch.', NULL, NULL, 'contact.php', NULL, NULL, 65, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'speaking', 'Speaking', 'Sharing the message', 'Vanessa speaks to audiences about why our surroundings matter so much, and how anyone can bring more nature, colour and calm into the places they live and work.', 'Talks are tailored to each audience. Popular themes include:', 'Speaking engagements', 'speaking.php', 'Invite Vanessa to speak', 'contact.php', 70, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'speaking-notice', NULL, NULL, 'Upcoming and past speaking engagements will be listed here soon.', NULL, NULL, NULL, NULL, NULL, 75, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'closing', NULL, 'Ready to reconnect your space with nature?', 'Start with a conversation. Tell Vanessa about your space, how you use it and how you''d like it to feel.', NULL, 'Book a consultation', 'services/consultations.php', 'Explore services', 'services.php', 80, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'services', 'seo', NULL, 'Services', 'Biophilic design, colour & design psychology, photography, workshops, speaking engagements and consultations by Vanessa Talbot Interior Designs.', NULL, NULL, NULL, NULL, NULL, 0, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'services', 'page-header', NULL, 'Services', 'Every service combines the science of how people respond to their surroundings with the art of beautiful design, for homes, workspaces, wellness centres, boutiques and events.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'services', 'process', 'How it works', 'A simple, collaborative process', NULL, NULL, NULL, NULL, NULL, NULL, 80, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'listen', 'Listen', 'We start with a conversation about your space, the people who use it and how you want it to feel.', NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'services' AND s.SectionKey = 'process' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'explore', 'Explore', 'Vanessa studies light, colour, layout and your connection to the outdoors, and looks for opportunities.', NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'services' AND s.SectionKey = 'process' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'design', 'Design', 'You receive clear, considered recommendations that balance beauty, wellbeing and practicality.', NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'services' AND s.SectionKey = 'process' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'bring-to-life', 'Bring to life', 'Whether you implement the plan yourself or with Vanessa''s support, your space begins to transform.', NULL, NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'services' AND s.SectionKey = 'process' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'services', 'closing', NULL, 'Not sure where to start?', 'A consultation is the perfect first step. Vanessa will help you understand your space and recommend the approach that suits you best.', NULL, 'Book a consultation', 'services/consultations.php', 'Ask a question', 'contact.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'global', 'footer', NULL, NULL, 'Biophilic interior design, colour & design psychology and photography for homes, workspaces, wellness centres, boutiques and events.', 'Ready to bring nature into your space? Let''s talk about what you have in mind.', 'Contact Vanessa', 'contact.php', NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'explore-heading', 'Explore', NULL, NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'global' AND s.SectionKey = 'footer' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'services-heading', 'Services', NULL, NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'global' AND s.SectionKey = 'footer' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'contact-heading', 'Get in touch', NULL, NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'global' AND s.SectionKey = 'footer' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

-- Each moved piece of text now lives in one place. These fields are emptied
-- only if they still hold the original seeded wording.
UPDATE HomePageContent SET IntroductionText = NULL WHERE HomePageID = 1 AND IntroductionText = 'Vanessa Talbot brings together a background in science and a passion for interior design to create spaces that do more than look beautiful. They help people feel well.\n\nHer work is built on a simple idea: the spaces we spend our days in shape how we think, feel and connect. By combining biophilic design, the psychology of colour and a photographer''s eye for light and detail, Vanessa designs interiors that are calm, personal and deeply connected to the natural world.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET Journey = NULL WHERE AboutID = 1 AND Journey = 'Vanessa''s path into design is what makes her approach different. She doesn''t see science and creativity as opposites. To her, they are two ways of understanding the same world.\n\nBiophilic design and colour & design psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET ScienceBackground = NULL WHERE AboutID = 1 AND ScienceBackground = 'A background in science taught Vanessa to observe carefully, ask questions and look for evidence. It also gave her a lasting respect for the natural systems that sustain us.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET DesignBackground = NULL WHERE AboutID = 1 AND DesignBackground = 'Her love of beauty, colour and composition drew her to interior design, where she discovered she could use both sides of her thinking at once.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET Philosophy = NULL WHERE AboutID = 1 AND Philosophy = 'Biophilia is our innate need to connect with nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.\n\nIn practice, that means designing with:\n\nLight and air: making the most of daylight, fresh air and views to the outside.\nLiving elements: plants, water and natural features that bring a space to life.\nNatural materials: wood, stone, linen and texture that invite touch and feel grounded.\nColour with purpose: palettes drawn from nature and chosen for how they make people feel.\nOrganic form: shapes, patterns and layouts that echo the natural world.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET SpeakingIntroduction = NULL WHERE AboutID = 1 AND SpeakingIntroduction = 'Vanessa speaks to audiences about why our surroundings matter so much, and how anyone can bring more nature, colour and calm into the places they live and work.\n\nTalks are tailored to each audience. Popular themes include:' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('006_page_sections');

-- --------------------------------------------------------------------------
-- 007: Footer menu headings are navigation labels, which stay in the code.
-- Runs once and removes them only if they still hold the original wording.
-- --------------------------------------------------------------------------
DELETE i FROM PageSectionItems i
JOIN PageSections s ON s.SectionID = i.SectionID
WHERE s.PageKey = 'global' AND s.SectionKey = 'footer'
  AND ((i.ItemKey = 'explore-heading' AND i.Title = 'Explore')
    OR (i.ItemKey = 'services-heading' AND i.Title = 'Services')
    OR (i.ItemKey = 'contact-heading' AND i.Title = 'Get in touch'))
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '007_remove_footer_navigation_labels');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('007_remove_footer_navigation_labels');


-- --------------------------------------------------------------------------
-- 008: The service is now called Colour & Design Psychology. Wording only;
-- web addresses, keys and column names are unchanged. Runs once, and changes
-- only the service name inside existing text.
-- --------------------------------------------------------------------------
UPDATE HomePageContent SET HeroEyebrow = 'Biophilic Interior Design · Colour & Design Psychology · Photography'
WHERE HomePageID = 1 AND HeroEyebrow = 'Test' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE HomePageContent
SET HeroEyebrow = REPLACE(REPLACE(REPLACE(HeroEyebrow, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    HeroSubtitle = REPLACE(REPLACE(REPLACE(HeroSubtitle, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    FeaturedCTATitle = REPLACE(REPLACE(REPLACE(FeaturedCTATitle, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    FeaturedCTAText = REPLACE(REPLACE(REPLACE(FeaturedCTAText, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaTitle = REPLACE(REPLACE(REPLACE(MetaTitle, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaDescription = REPLACE(REPLACE(REPLACE(MetaDescription, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE AboutContent
SET Biography = REPLACE(REPLACE(REPLACE(Biography, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Qualifications = REPLACE(REPLACE(REPLACE(Qualifications, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    CoreValues = REPLACE(REPLACE(REPLACE(CoreValues, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    SpeakingIntroduction = REPLACE(REPLACE(REPLACE(SpeakingIntroduction, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    SpeakingTopics = REPLACE(REPLACE(REPLACE(SpeakingTopics, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaTitle = REPLACE(REPLACE(REPLACE(MetaTitle, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaDescription = REPLACE(REPLACE(REPLACE(MetaDescription, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE AboutValues
SET Description = REPLACE(REPLACE(REPLACE(Description, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE Services
SET ServiceName = REPLACE(REPLACE(REPLACE(ServiceName, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    ShortDescription = REPLACE(REPLACE(REPLACE(ShortDescription, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Lead = REPLACE(REPLACE(REPLACE(Lead, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    FullDescription = REPLACE(REPLACE(REPLACE(FullDescription, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    IncludedItems = REPLACE(REPLACE(REPLACE(IncludedItems, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    IdealFor = REPLACE(REPLACE(REPLACE(IdealFor, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaTitle = REPLACE(REPLACE(REPLACE(MetaTitle, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    MetaDescription = REPLACE(REPLACE(REPLACE(MetaDescription, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE PageSections
SET Eyebrow = REPLACE(REPLACE(REPLACE(Eyebrow, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Heading = REPLACE(REPLACE(REPLACE(Heading, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Introduction = REPLACE(REPLACE(REPLACE(Introduction, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Body = REPLACE(REPLACE(REPLACE(Body, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

UPDATE PageSectionItems
SET Title = REPLACE(REPLACE(REPLACE(Title, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology'),
    Text = REPLACE(REPLACE(REPLACE(Text, 'Colour Psychology', 'Colour & Design Psychology'), 'Colour psychology', 'Colour & design psychology'), 'colour psychology', 'colour & design psychology')
WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '008_rename_colour_design_psychology');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('008_rename_colour_design_psychology');

-- --------------------------------------------------------------------------
-- 009: Edit stamps for project gallery images.
-- --------------------------------------------------------------------------
ALTER TABLE ProjectImages
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_ProjectImages_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_ProjectImages_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('009_project_image_stamps');

-- --------------------------------------------------------------------------
-- 010: Draft wording for the service, portfolio, contact and legal pages.
-- Runs once and only fills empty content. Contains no contact details:
-- those come from Website Settings.
-- --------------------------------------------------------------------------
INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-biophilic-design', 'approach', 'How it works', 'From first conversation to finished space', 'Every project is personal, and shaped around the way you live or work.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'consultation', 'Consultation', 'We talk through your space, your lifestyle and what you need the space to do for you.', 'chat', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'plans', 'Layouts, plans and mood boards', 'Your ideas take shape in layouts, plans and mood boards, so you can see the direction before anything changes.', 'pen', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'sourcing', 'Sourcing', 'Local and sustainable materials, fabrics, furniture, flooring and natural textures, chosen to suit your space and a realistic budget.', 'leaf', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'nature', 'Bringing nature in', 'Plants, water features, natural lighting and ventilation, art and accessories that reconnect the space with the natural world.', 'sprout', NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-biophilic-design', 'specialised', 'Specialised services', 'Sanctuaries and special occasions', NULL, NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'sanctuaries', 'Peaceful sanctuaries', 'Quiet, restorative places within your home or workspace, created around what helps you feel at ease.', 'sprout', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'specialised' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'events', 'Events and functions', 'Considered layouts for private and business functions that bring a sense of nature to the occasion.', 'group', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'specialised' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'table-settings', 'Table settings and décor', 'Natural textures, colour and detail that give every table and room a sense of occasion.', 'brush', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'specialised' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-biophilic-design', 'closing', NULL, 'Bring nature into your space', 'Tell Vanessa about your space and how you would like it to feel.', NULL, 'Enquire about this service', 'contact.php?service=biophilic-design', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-colour-psychology', 'approach', 'How it works', 'Design that responds to how you feel', 'A space works best when it matches both your emotional and your physical needs.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'understanding', 'Understanding you', 'We begin with how you want to feel in the space and what it needs to do for the people who use it.', 'person', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-colour-psychology' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'colour-light', 'Colour and light', 'Palettes are built around the way natural and artificial light changes through the day.', 'palette', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-colour-psychology' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'form', 'Form, texture and layout', 'Shape, texture and the arrangement of a room influence how it feels just as much as colour does.', 'brush', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-colour-psychology' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'plan', 'A clear plan', 'You receive considered recommendations that bring beauty, comfort and purpose together.', 'check', NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-colour-psychology' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-colour-psychology', 'closing', NULL, 'Find the right palette for your space', 'Tell Vanessa what the space is for and how you would like it to feel.', NULL, 'Enquire about this service', 'contact.php?service=colour-psychology', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-photography-services', 'approach', 'Areas of work', 'A way of seeing', 'Photography is about noticing: the light, the texture and the mood of a place.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'nature', 'Nature and botanical detail', 'Close, quiet studies of the natural world, from the pattern in a leaf to the colours of a landscape.', 'leaf', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-photography-services' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'interiors', 'Interiors', 'Photographs that show a finished space at its best, with its light, texture and atmosphere intact.', 'camera', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-photography-services' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'brands', 'Imagery for your business', 'Photography for designers, boutiques and wellness brands that want to share their spaces.', 'eye', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-photography-services' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-photography-services', 'closing', NULL, 'Talk to Vanessa about photography', 'Share what you would like to capture and where the images will be used.', NULL, 'Enquire about this service', 'contact.php?service=photography-services', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-workshops', 'approach', 'How it works', 'Learn by doing', 'Sessions are interactive, practical and tailored to the group.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'explore', 'Explore', 'Participants look at how their surroundings affect the way they feel and work.', 'eye', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-workshops' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'try', 'Try it', 'Hands-on activities turn the principles of biophilic design and colour & design psychology into something tangible.', 'brush', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-workshops' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'take-home', 'Take it home', 'Everyone leaves with simple, achievable changes to make at home or at work.', 'sprout', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-workshops' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-workshops', 'closing', NULL, 'Plan a workshop', 'Tell Vanessa about your group and what you would like them to take away.', NULL, 'Enquire about this service', 'contact.php?service=workshops', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-speaking-engagements', 'approach', 'Audiences', 'Talks shaped around your audience', 'Each talk is tailored to the people in the room and what will be most useful to them.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'conferences', 'Conferences and industry events', 'Ideas on designing with nature for design, property and business audiences.', 'mic', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-speaking-engagements' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'wellness', 'Corporate wellness days', 'Why surroundings matter to wellbeing, and what teams can change straight away.', 'leaf', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-speaking-engagements' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'community', 'Community groups', 'Accessible, practical conversations about bringing nature, colour and calm into everyday life.', 'group', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-speaking-engagements' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-speaking-engagements', 'closing', NULL, 'Invite Vanessa to speak', 'Share your event, your audience and the date you have in mind.', NULL, 'Enquire about this service', 'contact.php?service=speaking-engagements', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-consultations', 'approach', 'What to expect', 'A focused conversation about your space', 'A consultation can be the start of a larger project, or expert direction you carry out yourself.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'before', 'Before we meet', 'Tell Vanessa a little about your space, how you use it and what is not working.', 'chat', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-consultations' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'together', 'Together in your space', 'We look at light, colour, layout and your connection to the outdoors.', 'eye', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-consultations' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'next', 'Clear next steps', 'You leave with practical, prioritised recommendations that are specific to you.', 'check', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'service-consultations' AND s.SectionKey = 'approach' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-consultations', 'closing', NULL, 'Book a consultation', 'Tell Vanessa about your space and we will arrange a time.', NULL, 'Enquire about this service', 'contact.php?service=consultations', 'View all services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio', 'page-header', NULL, 'Portfolio', 'Explore Vanessa''s work across biophilic interiors, colour-led design and photography.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio', 'categories', 'Explore', 'Spaces with a sense of place', NULL, NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'biophilic', 'Biophilic Interior Design', 'Interiors brought to life with natural light, planting and organic materials.', 'leaf', 'portfolio/biophilic-interior-design.php', 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'portfolio' AND s.SectionKey = 'categories' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'colour', 'Colour & Design Psychology', 'Spaces designed around how people want to feel in them.', 'palette', 'portfolio/colour-psychology.php', 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'portfolio' AND s.SectionKey = 'categories' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'photography', 'Photography', 'Nature, botanical detail and interiors seen through Vanessa''s lens.', 'camera', 'portfolio/photography-gallery.php', 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'portfolio' AND s.SectionKey = 'categories' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'collaborators', 'Collaborators', 'The people and businesses Vanessa works alongside.', 'group', 'portfolio/collaborators.php', 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'portfolio' AND s.SectionKey = 'categories' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio', 'featured', 'Featured', 'Selected projects', NULL, NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio', 'closing', NULL, 'Have a space in mind?', 'Tell Vanessa about your home, workspace or event, and how you would like it to feel.', NULL, 'Get in touch', 'contact.php', 'Explore services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-biophilic-interior-design', 'page-header', NULL, 'Biophilic Interior Design', 'Homes, workspaces and wellness spaces reconnected with the natural world.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-biophilic-interior-design', 'intro', 'The work', 'Designing with nature', 'Natural light, planting, water and organic materials, brought together in personal and functional spaces.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-biophilic-interior-design', 'empty', NULL, 'Projects coming soon', 'Vanessa''s project stories are being prepared and will appear here soon.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-biophilic-interior-design', 'closing', NULL, 'Have a space in mind?', 'Tell Vanessa about your home, workspace or event, and how you would like it to feel.', NULL, 'Get in touch', 'contact.php', 'Explore services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-colour-psychology', 'page-header', NULL, 'Colour & Design Psychology', 'Spaces designed around how people want to feel in them.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-colour-psychology', 'intro', 'The work', 'Colour and design with purpose', 'Palettes, textures and layouts chosen to match the emotional and physical needs of the people who use each space.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-colour-psychology', 'empty', NULL, 'Projects coming soon', 'Vanessa''s project stories are being prepared and will appear here soon.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-colour-psychology', 'closing', NULL, 'Have a space in mind?', 'Tell Vanessa about your home, workspace or event, and how you would like it to feel.', NULL, 'Get in touch', 'contact.php', 'Explore services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-photography-gallery', 'page-header', NULL, 'Photography', 'Nature, botanical detail and interiors seen through Vanessa''s lens.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-photography-gallery', 'intro', 'The work', 'Light, texture and atmosphere', 'Images that invite you to connect with colour, shape and form in the natural world.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-photography-gallery', 'empty', NULL, 'Gallery coming soon', 'A selection of Vanessa''s photographs will appear here soon.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-photography-gallery', 'closing', NULL, 'Have a space in mind?', 'Tell Vanessa about your home, workspace or event, and how you would like it to feel.', NULL, 'Get in touch', 'contact.php', 'Explore services', 'services.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-collaborators', 'page-header', NULL, 'Collaborators', 'The people and businesses Vanessa works alongside.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-collaborators', 'intro', 'Working together', 'Better spaces, made together', 'Good design depends on good partnerships, from local makers and suppliers to fellow creatives.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-collaborators', 'empty', NULL, 'Collaborators coming soon', 'The people and businesses Vanessa works with will be introduced here soon.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-collaborators', 'closing', NULL, 'Interested in working together?', 'Vanessa would be glad to hear from makers, suppliers and fellow creatives.', NULL, 'Get in touch', 'contact.php', 'View the portfolio', 'portfolio.php', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'page-header', NULL, 'Contact', 'Whether it''s your home, your workspace, a wellness centre, a boutique or an event, Vanessa would love to hear what you have in mind.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'details', 'Get in touch', 'Contact details', 'The easiest way to start is with a phone call or an email.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'location', 'Location', 'Where Vanessa is based', 'Visits are by appointment. Get in touch to discuss your location and project.', NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'form', 'Enquiries', 'Send a message', 'Tell us a little about your space and what you have in mind.', NULL, NULL, NULL, NULL, NULL, 40, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'faq', 'Questions', 'Frequently asked questions', NULL, NULL, NULL, NULL, NULL, NULL, 50, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'what-is-biophilic', 'What is biophilic design?', 'Biophilic design brings the natural world into the spaces where we live and work, through light, plants, water, natural materials and organic form, to support health and wellbeing.', NULL, NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'who', 'What kinds of spaces do you work with?', 'Vanessa works with homes, workspaces, health and wellness centres, boutiques, and events and functions.', NULL, NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'included', 'What does a design project include?', 'Projects can include consultation, layouts, plans, mood boards, and the sourcing of local and sustainable materials, furniture, plants and accessories. The scope is agreed with you at the start.', NULL, NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'full-redesign', 'Do I need a full redesign?', 'Not at all. A consultation can give you clear, practical direction to carry out yourself, or be the first step in a larger project.', NULL, NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'budget', 'Can you work within my budget?', 'Yes. Every project starts with a realistic budget, agreed with you before any work begins.', NULL, NULL, 50, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'events', 'Do you help with events?', 'Yes. Vanessa offers event layouts, table settings and décor for private and business functions.', NULL, NULL, 60, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

UPDATE SitePages SET Title = 'Privacy Policy', Content = COALESCE(Content, 'Vanessa Talbot Interior Designs respects your privacy and is committed to protecting your personal information in line with the Protection of Personal Information Act, 2013 (POPIA).\n\n## Who is responsible for your information\n\nVanessa Talbot Interior Designs is the responsible party for personal information collected through this website. Vanessa Talbot is the Information Officer. You can reach her using the details on our Contact page.\n\n## What we collect\n\nWe only collect personal information that you choose to give us, or that is needed to run this website:\n\n- Your name, email address and phone number, if you contact us\n- Details of your enquiry or project\n- Technical information sent by your browser, such as your IP address, which our hosting provider records for security\n\n## How we use your information\n\nWe use your information to:\n\n- Respond to your enquiry and provide the services you ask for\n- Send you marketing, only if you have agreed to receive it\n- Keep this website secure and working properly\n- Meet our legal obligations\n\n## Sharing your information\n\nWe do not sell your personal information. We only share it with service providers who help us run this website and our business, such as our hosting provider, and only as far as is needed, or where the law requires it.\n\nThis website uses Google Fonts. When a page loads, your browser connects to Google to fetch the fonts, which shares your IP address with Google.\n\n## Keeping your information safe\n\nWe take reasonable technical and organisational steps to protect your personal information against loss, misuse and unauthorised access.\n\n## How long we keep it\n\nWe keep personal information only for as long as we need it for the purposes above, or as the law requires.\n\n## Cookies\n\nThis website uses a cookie only for administrators who sign in. It does not use cookies to track visitors. If that changes, we will update this policy and ask for your consent where required.\n\n## Your rights\n\nUnder POPIA you may ask to see the personal information we hold about you, ask us to correct or delete it, and object to its use, including for marketing. You may unsubscribe from marketing at any time.\n\nIf you are not satisfied with how we have handled your information, you may complain to the Information Regulator of South Africa.\n\n## Changes to this policy\n\nWe may update this policy from time to time. The latest version will always be on this page.'), MetaDescription = COALESCE(MetaDescription, 'How Vanessa Talbot Interior Designs collects, uses and protects personal information.'), Status = 'Published'
WHERE Slug = 'privacy-policy' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

UPDATE SitePages SET Title = 'Terms and Conditions', Content = COALESCE(Content, 'By using this website you agree to these terms. If you do not agree, please do not use the website.\n\n## About this website\n\nThis website is operated by Vanessa Talbot Interior Designs in South Africa. It provides general information about our services, work and ideas.\n\n## Information on this website\n\nWe take care to keep this website accurate and up to date, but its content is general information and not professional advice for your particular circumstances. Please contact us before relying on it for a project.\n\n## Our services\n\nDesign services, workshops, speaking engagements and consultations are subject to a separate quotation or agreement, which sets out the scope, fees and terms for that work.\n\n## Your use of this website\n\nYou agree not to:\n\n- Use this website in any way that is unlawful or harmful\n- Attempt to gain unauthorised access to the website or its systems\n- Copy or reuse its content except as allowed in our Copyright Notice\n\n## Links to other websites\n\nThis website may link to other websites. We are not responsible for their content or privacy practices.\n\n## Limitation of liability\n\nTo the extent permitted by law, Vanessa Talbot Interior Designs is not liable for any loss arising from your use of this website or from relying on its content. Nothing in these terms limits any rights you have under the Consumer Protection Act, 2008.\n\n## Governing law\n\nThese terms are governed by the laws of the Republic of South Africa.\n\n## Changes to these terms\n\nWe may update these terms from time to time. The latest version will always be on this page.'), MetaDescription = COALESCE(MetaDescription, 'The terms that apply to your use of this website.'), Status = 'Published'
WHERE Slug = 'terms-and-conditions' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

UPDATE SitePages SET Title = 'Copyright Notice', Content = COALESCE(Content, 'All content on this website is the property of Vanessa Talbot Interior Designs or is used with permission, and is protected by copyright law.\n\n## What is protected\n\nThis includes:\n\n- Text and written content\n- Photographs, images and graphics\n- Logos and branding\n- Designs, mood boards and project material\n- The design of the website itself\n\n## What you may do\n\nYou may view this website and share links to its pages.\n\n## What you may not do\n\nWithout our prior written permission, you may not copy, reproduce, modify, distribute, publish, sell or otherwise use any part of this website or its content.\n\n## Requesting permission\n\nTo ask about using our content, please get in touch through our Contact page.'), MetaDescription = COALESCE(MetaDescription, 'Copyright information for the content of this website.'), Status = 'Published'
WHERE Slug = 'copyright' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '010_seed_public_pages');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('010_seed_public_pages');


-- --------------------------------------------------------------------------
-- 011: Captions and edit stamps for the Media Library.
-- --------------------------------------------------------------------------
ALTER TABLE MediaLibrary
    ADD COLUMN IF NOT EXISTS Caption VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_MediaLibrary_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_MediaLibrary_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('011_media_library_details');
