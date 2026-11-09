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
SELECT 'Colour Psychology', 'colour-psychology', 'palette', 'Colour chosen with purpose, to influence mood, energy and behaviour in the way each space needs.', 'Colour is never just decoration. It shapes how a room feels, and how we feel in it.', 'Vanessa combines an understanding of how colour affects mood and perception with a designer''s eye for harmony and balance. Whether a space needs to soothe, energise, welcome or inspire, the palette is built around the people who use it and what they need it to do.', 'Colour consultation based on how the space is used and who uses it\nConsidered palettes for walls, furnishings and finishes\nGuidance on natural and artificial light and how it changes colour\nBrand-aligned colour for commercial and retail spaces', 'Residential homes\nBoutiques\nWorkspaces', 'Published', 20
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Photography', 'photography-services', 'camera', 'Nature and interior photography that captures texture, light and atmosphere.', 'Photography is where Vanessa''s eye for detail and love of the natural world meet.', 'From botanical detail and natural landscapes to finished interiors, Vanessa''s photography is about noticing: the way light falls, the pattern in a leaf, the mood of a room. Selected images are available as prints through the photography store, bringing a piece of nature into your own space.', 'Nature and botanical fine-art photography\nPhotography prints available through the online store\nInterior photography that shows a space at its best\nImagery for designers, boutiques and wellness brands', 'Residential homes\nBoutiques\nHealth & wellness centres', 'Published', 30
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Speaking Engagements', 'speaking-engagements', 'mic', 'Engaging talks on biophilic design, colour psychology and nature-centred wellbeing.', 'Ideas that change how audiences see the spaces they live and work in.', 'Vanessa speaks on the science and the art of designing with nature: why our surroundings matter so much to our wellbeing, and what anyone can do about it. Talks are tailored to the audience, whether a conference, a corporate wellness day, a community group or an industry event.', 'Keynotes and talks tailored to your audience\nTopics spanning biophilic design, colour and wellbeing\nPractical takeaways audiences can use straight away\nPanel discussions and question-and-answer sessions', 'Events\nWorkspaces\nHealth & wellness centres', 'Published', 40
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '004_seed_services');

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
SELECT 'Workshops', 'workshops', 'group', 'Hands-on, interactive sessions that teach people to bring nature and colour into their own spaces.', 'Learn by doing, and leave with ideas you can put into practice at home or at work.', 'Vanessa''s workshops turn the principles of biophilic design and colour psychology into practical, enjoyable experiences. Participants explore how their surroundings affect them, then work through simple, achievable changes. Sessions suit teams, wellness programmes, retail events and community groups.', 'Interactive group sessions with hands-on activities\nPractical guides and worksheets to take home\nContent tailored to teams, clients or communities\nOptions for corporate wellness and team-building days', 'Workspaces\nEvents\nHealth & wellness centres', 'Published', 50
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
SET     HeroEyebrow = COALESCE(HeroEyebrow, 'Biophilic Interior Design · Colour Psychology · Photography'),
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
    MetaDescription = COALESCE(MetaDescription, 'Vanessa Talbot Interior Designs creates nature-centred interiors through biophilic design, colour psychology and photography, for homes, workspaces, wellness centres, boutiques and events.')
WHERE HomePageID = 1 AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '005_seed_home_about_content');

UPDATE AboutContent
SET     Biography = COALESCE(Biography, 'Vanessa Talbot has always been fascinated by the relationship between people and the places they spend their lives in.\n\nIt is a curiosity that has carried her from science to interior design, and into photography. Each one has taught her to look a little more closely, at how light moves through a room, how colour changes a mood, and how a single living plant can soften an entire space.\n\nToday, Vanessa Talbot Interior Designs brings those threads together. Vanessa works with homeowners, businesses, wellness practitioners, retailers and event organisers to create interiors that are beautiful, personal and good for the people in them.'),
    Journey = COALESCE(Journey, 'Vanessa''s path into design is what makes her approach different. She doesn''t see science and creativity as opposites. To her, they are two ways of understanding the same world.\n\nBiophilic design and colour psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.'),
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
SELECT s.SectionID, 'colour-psychology', 'Colour Psychology', 'Palettes designed to calm, energise or welcome, depending on what the space needs.', 'palette', 'portfolio/colour-psychology.php', 20, 'Published'
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
SELECT s.SectionID, 'colour-psychology', 'Colour psychology', NULL, NULL, NULL, 20, 'Published'
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
SELECT s.SectionID, 'nature', 'Nature as the bridge', 'Biophilic design and colour psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.', 'sprout', NULL, 30, 'Published'
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
SELECT 'services', 'seo', NULL, 'Services', 'Biophilic design, colour psychology, photography, workshops, speaking engagements and consultations by Vanessa Talbot Interior Designs.', NULL, NULL, NULL, NULL, NULL, 0, 'Published'
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
SELECT 'global', 'footer', NULL, NULL, 'Biophilic interior design, colour psychology and photography for homes, workspaces, wellness centres, boutiques and events.', 'Ready to bring nature into your space? Let''s talk about what you have in mind.', 'Contact Vanessa', 'contact.php', NULL, NULL, 10, 'Published'
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

UPDATE AboutContent SET Journey = NULL WHERE AboutID = 1 AND Journey = 'Vanessa''s path into design is what makes her approach different. She doesn''t see science and creativity as opposites. To her, they are two ways of understanding the same world.\n\nBiophilic design and colour psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET ScienceBackground = NULL WHERE AboutID = 1 AND ScienceBackground = 'A background in science taught Vanessa to observe carefully, ask questions and look for evidence. It also gave her a lasting respect for the natural systems that sustain us.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET DesignBackground = NULL WHERE AboutID = 1 AND DesignBackground = 'Her love of beauty, colour and composition drew her to interior design, where she discovered she could use both sides of her thinking at once.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET Philosophy = NULL WHERE AboutID = 1 AND Philosophy = 'Biophilia is our innate need to connect with nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.\n\nIn practice, that means designing with:\n\nLight and air: making the most of daylight, fresh air and views to the outside.\nLiving elements: plants, water and natural features that bring a space to life.\nNatural materials: wood, stone, linen and texture that invite touch and feel grounded.\nColour with purpose: palettes drawn from nature and chosen for how they make people feel.\nOrganic form: shapes, patterns and layouts that echo the natural world.' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

UPDATE AboutContent SET SpeakingIntroduction = NULL WHERE AboutID = 1 AND SpeakingIntroduction = 'Vanessa speaks to audiences about why our surroundings matter so much, and how anyone can bring more nature, colour and calm into the places they live and work.\n\nTalks are tailored to each audience. Popular themes include:' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '006_page_sections');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('006_page_sections');
