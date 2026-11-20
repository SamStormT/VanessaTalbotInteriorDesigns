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


-- --------------------------------------------------------------------------
-- 012: Image optimisation. Width, Height and FileSize describe the optimised
-- main image; the Original columns describe the private, untouched upload.
-- Images uploaded earlier have no original on record until
-- database/optimise-media.php is run.
-- --------------------------------------------------------------------------
ALTER TABLE MediaLibrary
    ADD COLUMN IF NOT EXISTS OriginalPath VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS OriginalWidth INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS OriginalHeight INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS OriginalFileSize INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS Optimisation ENUM('Optimised','Unchanged','Failed') NULL;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('012_media_optimisation');


-- --------------------------------------------------------------------------
-- 013: Blog. Edit stamps on categories (like every other editable table),
-- "Blog" as a subscriber source, the blog page's starting wording, a link
-- from the home page's blog section, and starting categories taken from the
-- topics already listed on the home page. Wording and categories run once
-- and only fill what is empty, so later edits are never overwritten.
-- --------------------------------------------------------------------------
ALTER TABLE BlogCategories
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_BlogCategories_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_BlogCategories_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE Subscribers
    MODIFY Source ENUM('Newsletter Popup','Footer Subscribe','Homepage Section','Contact Form','Admin Added','Import','Blog') NOT NULL;

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, DisplayOrder, Status)
SELECT 'blog', 'page-header', NULL, 'Blog', 'Practical ideas on biophilic design, colour and design psychology, and wellbeing at home and at work, with the stories behind the photographs.', 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, DisplayOrder, Status)
SELECT 'blog', 'empty', NULL, 'The first articles are on their way', 'Vanessa will be sharing practical ideas on biophilic design, the psychology of colour, nature-centred wellbeing and the stories behind her photography. Join the newsletter below to be the first to read them.', 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, DisplayOrder, Status)
SELECT 'blog', 'no-results', NULL, 'No articles found', 'Try a different word, or browse all articles.', 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, DisplayOrder, Status)
SELECT 'blog', 'post-newsletter', 'Newsletter', 'Enjoyed this article?', 'New articles, seasonal ideas and first news of workshops and talks, straight to your inbox.', 40, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

UPDATE PageSections SET ButtonText = 'Read the blog', ButtonLink = 'blog.php'
WHERE PageKey = 'home' AND SectionKey = 'blog' AND ButtonText IS NULL AND ButtonLink IS NULL
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO BlogCategories (CategoryName, Slug, Description)
SELECT 'Biophilic design', 'biophilic-design', 'Bringing nature into the places we live and work.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO BlogCategories (CategoryName, Slug, Description)
SELECT 'Colour & design psychology', 'colour-design-psychology', 'How colour, light and layout shape the way a space feels.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO BlogCategories (CategoryName, Slug, Description)
SELECT 'Wellbeing at home', 'wellbeing-at-home', 'Calmer, healthier homes, one room at a time.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO BlogCategories (CategoryName, Slug, Description)
SELECT 'Healthier workspaces', 'healthier-workspaces', 'Offices and studios that support focus and wellbeing.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO BlogCategories (CategoryName, Slug, Description)
SELECT 'Photography', 'photography', 'The stories behind the photographs.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '013_blog');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('013_blog');


-- --------------------------------------------------------------------------
-- 014: Newsletter campaigns. Campaign content (greeting, introduction,
-- featured article, quote, read-more button, supporting content), the link
-- to the blog post it came from, a Ready status and a Sending status, safe
-- resumable sending (each recipient row is claimed before it is emailed),
-- a Skipped result for people who unsubscribed mid-send, and a history of
-- test emails. Structure only: no content changes.
-- --------------------------------------------------------------------------
ALTER TABLE NewsletterCampaigns
    MODIFY Status ENUM('Draft','Ready','Scheduled','Sending','Sent','Archived') NOT NULL DEFAULT 'Draft',
    ADD COLUMN IF NOT EXISTS BlogID INT UNSIGNED NULL AFTER CampaignName,
    ADD COLUMN IF NOT EXISTS PreviewText VARCHAR(255) NULL AFTER Subject,
    ADD COLUMN IF NOT EXISTS Greeting VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS IntroMessage TEXT NULL,
    ADD COLUMN IF NOT EXISTS FeatureTitle VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS FeatureSummary TEXT NULL,
    ADD COLUMN IF NOT EXISTS FeatureImage VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS FeatureImageAlt VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS FeatureBody MEDIUMTEXT NULL,
    ADD COLUMN IF NOT EXISTS ReadMoreText VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS ReadMoreLink VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS QuoteText TEXT NULL,
    ADD COLUMN IF NOT EXISTS QuoteAuthor VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS SendStartedDate DATETIME NULL,
    ADD COLUMN IF NOT EXISTS LastEditedBy INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS LastEditedDate DATETIME NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_Blog FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_Blog (BlogID)
        REFERENCES BlogPosts (BlogID) ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_LastEditedBy FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_LastEditedBy (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE CampaignRecipients
    MODIFY Status ENUM('Pending','Sending','Sent','Failed','Skipped') NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS ClaimToken CHAR(32) NULL,
    ADD COLUMN IF NOT EXISTS ClaimedDate DATETIME NULL,
    ADD KEY IF NOT EXISTS idx_CampaignRecipients_Claim (ClaimToken);

CREATE TABLE IF NOT EXISTS CampaignTestSends (
    TestID       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    CampaignID   INT UNSIGNED NOT NULL,
    SentTo       VARCHAR(254) NOT NULL,
    SentBy       INT UNSIGNED NULL,
    SentDate     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Successful   TINYINT(1) NOT NULL DEFAULT 0,
    ErrorMessage VARCHAR(500) NULL,
    PRIMARY KEY (TestID),
    KEY idx_CampaignTestSends_Campaign (CampaignID, SentDate),
    CONSTRAINT fk_CampaignTestSends_Campaign FOREIGN KEY (CampaignID)
        REFERENCES NewsletterCampaigns (CampaignID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_CampaignTestSends_SentBy FOREIGN KEY (SentBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('014_newsletter_campaigns');


-- --------------------------------------------------------------------------
-- 015: Version history. Before any CMS save changes a record, the previous
-- values of the changed fields are kept here, so a change can be undone
-- ("Restore this version"). Up to 50 versions are kept per record.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ContentVersions (
    VersionID   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    TableName   VARCHAR(64) NOT NULL,
    RecordID    BIGINT UNSIGNED NOT NULL,
    OldValues   MEDIUMTEXT NOT NULL COMMENT 'JSON: field => value before the change',
    NewValues   MEDIUMTEXT NOT NULL COMMENT 'JSON: field => value after the change',
    ChangedBy   INT UNSIGNED NULL,
    ChangedDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Note        VARCHAR(255) NULL,
    PRIMARY KEY (VersionID),
    KEY idx_ContentVersions_Record (TableName, RecordID, VersionID),
    CONSTRAINT fk_ContentVersions_ChangedBy FOREIGN KEY (ChangedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('015_content_versions');

-- --------------------------------------------------------------------------
-- 016: Sharing and scheduling. A default picture for links shared on social
-- media; up to three more blog articles in a newsletter (a round-up);
-- scheduled sending (ScheduledDate and the Scheduled status already exist);
-- and the welcome email sent when someone confirms their subscription. The
-- welcome email's starting wording is filled in once and starts switched
-- off, so nothing is sent until it has been read and switched on.
-- --------------------------------------------------------------------------
ALTER TABLE WebsiteSettings
    ADD COLUMN IF NOT EXISTS ShareImage VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS ShareImageAlt VARCHAR(255) NULL;

ALTER TABLE NewsletterCampaigns
    ADD COLUMN IF NOT EXISTS MorePost1ID INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS MorePost2ID INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS MorePost3ID INT UNSIGNED NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_More1 FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_More1 (MorePost1ID)
        REFERENCES BlogPosts (BlogID) ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_More2 FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_More2 (MorePost2ID)
        REFERENCES BlogPosts (BlogID) ON UPDATE CASCADE ON DELETE SET NULL,
    ADD CONSTRAINT fk_NewsletterCampaigns_More3 FOREIGN KEY IF NOT EXISTS fk_NewsletterCampaigns_More3 (MorePost3ID)
        REFERENCES BlogPosts (BlogID) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS NewsletterSettings (
    SettingID         INT UNSIGNED NOT NULL DEFAULT 1,
    WelcomeEnabled    TINYINT(1) NOT NULL DEFAULT 0,
    WelcomeSubject    VARCHAR(255) NULL,
    WelcomeGreeting   VARCHAR(150) NULL,
    WelcomeMessage    TEXT NULL,
    WelcomeShowLatest TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Add the three newest articles',
    LastEditedBy      INT UNSIGNED NULL,
    LastEditedDate    DATETIME NULL,
    UpdatedDate       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (SettingID),
    CONSTRAINT chk_NewsletterSettings_SingleRow CHECK (SettingID = 1),
    CONSTRAINT fk_NewsletterSettings_LastEditedBy FOREIGN KEY (LastEditedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO NewsletterSettings (SettingID, WelcomeEnabled, WelcomeSubject, WelcomeGreeting, WelcomeMessage)
SELECT 1, 0, 'Welcome, and thank you for subscribing', 'Hi {first_name},',
       'Thank you for confirming your subscription. I''m so glad you''re here.\n\nEach newsletter brings you a new article from the blog: ideas for bringing nature indoors, how colour and design shape the way a space feels, and the stories behind my projects and photographs.\n\nI look forward to sharing them with you.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '016_sharing_and_scheduling');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('016_sharing_and_scheduling');

-- --------------------------------------------------------------------------
-- 017: Before-and-after photos, testimonials on the website, the consultation
-- booking page, and the brand's tagline style. A project image can have a
-- "before" photo of the same view (shown as a slider). Wording runs once and
-- only fills what is empty or still the original, so later edits are kept.
-- --------------------------------------------------------------------------
ALTER TABLE ProjectImages
    ADD COLUMN IF NOT EXISTS BeforeImage VARCHAR(500) NULL AFTER Caption,
    ADD COLUMN IF NOT EXISTS BeforeImageAlt VARCHAR(255) NULL AFTER BeforeImage;

UPDATE WebsiteSettings SET Tagline = 'The balance of art, science & nature in spaces'
WHERE SettingID = 1 AND Tagline = 'The Balance of Art, Science and Nature in Spaces'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'testimonials', 'Kind words', 'What clients say', NULL, NULL, NULL, NULL, NULL, NULL, 65, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'consultation', 'page-header', NULL, 'Book a consultation', 'Tell me a little about your space and the days that suit you, and I''ll be in touch to confirm a time.', NULL, NULL, NULL, NULL, NULL, 10, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'consultation', 'form', 'Your consultation', 'Tell me about your space', 'There''s no obligation: this simply helps me prepare and suggest a time.', NULL, NULL, NULL, NULL, NULL, 20, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'consultation', 'next', NULL, 'What happens next', NULL, NULL, NULL, NULL, NULL, NULL, 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'confirm', 'I confirm a time', 'I''ll reply to suggest a date and time from the ones you chose, or another that suits us both.', 'chat', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'consultation' AND s.SectionKey = 'next' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'meet', 'We meet', 'We talk about how you live and work, and what you would like the space to feel like. Where we meet is agreed when the time is confirmed.', 'leaf', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'consultation' AND s.SectionKey = 'next' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'ideas', 'You leave with ideas', 'Practical next steps you can act on, whether you go on to a full design or make the changes yourself.', 'sprout', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'consultation' AND s.SectionKey = 'next' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

UPDATE PageSections SET ButtonText = 'Book a consultation', ButtonLink = 'book-consultation.php'
WHERE PageKey = 'service-consultations' AND SectionKey = 'closing' AND ButtonLink = 'contact.php?service=consultations'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '017_consultations_testimonials');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('017_consultations_testimonials');

-- --------------------------------------------------------------------------
-- 018: The booking form no longer asks where to meet (that is agreed when the
-- time is confirmed), so the "We meet" step stops promising a place. Only
-- changed if it still has the original wording.
-- --------------------------------------------------------------------------
UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'We talk about how you live and work, and what you would like the space to feel like. Where we meet is agreed when the time is confirmed.'
WHERE s.PageKey = 'consultation' AND s.SectionKey = 'next' AND i.ItemKey = 'meet'
  AND i.Text = 'At your space, or online if that is easier. We talk about how you live and work, and what you would like the space to feel like.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '018_booking_area');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('018_booking_area');

-- --------------------------------------------------------------------------
-- 019: Search engines and AI assistants (SEO and GEO). Where the business
-- works, the designer's name and title (for "who is behind this"), a short
-- bio for articles, whether AI companies may train on the site's text and
-- photos (off by default; AI search tools are always allowed), the Google and
-- Bing verification codes, and search wording for the pages that had none.
-- Starting wording runs once and only fills what is empty.
-- --------------------------------------------------------------------------
ALTER TABLE WebsiteSettings
    ADD COLUMN IF NOT EXISTS ServiceAreas TEXT NULL COMMENT 'One place or arrangement per line',
    ADD COLUMN IF NOT EXISTS OwnerName VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS OwnerTitle VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS AllowAITraining TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS GoogleVerification VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS BingVerification VARCHAR(100) NULL;

ALTER TABLE AboutContent
    ADD COLUMN IF NOT EXISTS ShortBio VARCHAR(500) NULL;

UPDATE WebsiteSettings SET
    ServiceAreas = COALESCE(ServiceAreas, 'Pietermaritzburg and the KwaZulu-Natal Midlands\nOnline consultations across South Africa\nInternational projects by arrangement (travel costs are covered by the client)'),
    OwnerName    = COALESCE(OwnerName, 'Vanessa Talbot'),
    OwnerTitle   = COALESCE(OwnerTitle, 'Interior designer')
WHERE SettingID = 1 AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio', 'seo', NULL, NULL, 'Interior design projects by Vanessa Talbot in Pietermaritzburg and the KwaZulu-Natal Midlands: biophilic interiors, colour-led spaces and photography.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-biophilic-interior-design', 'seo', NULL, NULL, 'Biophilic interior design projects: homes and workplaces brought closer to nature with plants, natural materials and light, in KwaZulu-Natal and beyond.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-colour-psychology', 'seo', NULL, NULL, 'Colour and design psychology projects: spaces planned around how colour, light and layout make people feel.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-photography-gallery', 'seo', NULL, NULL, 'Nature and interior photography by Vanessa Talbot, from the KwaZulu-Natal Midlands and further afield.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'portfolio-collaborators', 'seo', NULL, NULL, 'The landscapers, makers and specialists Vanessa Talbot Interior Designs works with.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'blog', 'seo', NULL, NULL, 'Practical ideas on biophilic design, colour and design psychology and wellbeing at home and at work, from interior designer Vanessa Talbot.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'contact', 'seo', NULL, NULL, 'Contact Vanessa Talbot Interior Designs in Pietermaritzburg: in person in the KZN Midlands, online across South Africa, and further afield by arrangement.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'consultation', 'seo', NULL, 'Book an interior design consultation', 'Book a consultation with interior designer Vanessa Talbot: tell her about your space and the days that suit you, in Pietermaritzburg or online.', NULL, NULL, NULL, NULL, NULL, 1, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '019_search_and_ai');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('019_search_and_ai');

-- --------------------------------------------------------------------------
-- 020: Security and privacy. Two-step sign-in for admin accounts (an
-- authenticator app code, with one-time backup codes kept only as hashes);
-- how long personal details are kept (POPIA), cleaned up automatically once
-- a day; privacy-friendly visitor statistics (daily counts per page and per
-- referring website: no cookies, no IP addresses, nothing about the
-- visitor); gift voucher enquiries, and a gift voucher block on the
-- Services page (wording runs once and only fills what is empty).
-- --------------------------------------------------------------------------
ALTER TABLE AdminUsers
    ADD COLUMN IF NOT EXISTS TwoFactorSecret VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS TwoFactorEnabledDate DATETIME NULL,
    ADD COLUMN IF NOT EXISTS TwoFactorLastStep BIGINT NULL COMMENT 'Stops one code being used twice',
    ADD COLUMN IF NOT EXISTS TwoFactorRecovery TEXT NULL COMMENT 'JSON list of hashed one-time backup codes';

ALTER TABLE WebsiteSettings
    ADD COLUMN IF NOT EXISTS KeepLoginDays SMALLINT UNSIGNED NOT NULL DEFAULT 90,
    ADD COLUMN IF NOT EXISTS KeepMessagesMonths SMALLINT UNSIGNED NOT NULL DEFAULT 24,
    ADD COLUMN IF NOT EXISTS KeepActivityMonths SMALLINT UNSIGNED NOT NULL DEFAULT 24,
    ADD COLUMN IF NOT EXISTS KeepUnsubscribedMonths SMALLINT UNSIGNED NOT NULL DEFAULT 24,
    ADD COLUMN IF NOT EXISTS KeepStatisticsMonths SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    ADD COLUMN IF NOT EXISTS LastCleanupDate DATETIME NULL;

CREATE TABLE IF NOT EXISTS PageViews (
    ViewDate DATE NOT NULL,
    Path     VARCHAR(255) NOT NULL,
    Views    INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ViewDate, Path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PageReferrers (
    ViewDate DATE NOT NULL,
    Source   VARCHAR(100) NOT NULL COMMENT 'The other website, for example google.com',
    Views    INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ViewDate, Source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ContactMessages
    MODIFY EnquiryType ENUM('General','Biophilic Design','Colour Psychology','Photography','Workshop','Speaking Engagement','Collaboration','Consultation','Gift Voucher') NOT NULL DEFAULT 'General';

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'services', 'gift-vouchers', 'Gift vouchers', 'Give the gift of a space that feels good', 'A consultation or design service makes a thoughtful gift for a new home, a birthday or a fresh start. Ask about a gift voucher for the amount or service you have in mind.', NULL, 'Ask about a gift voucher', 'contact.php?service=gift-voucher#contact-form', NULL, NULL, 70, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '020_security_privacy');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('020_security_privacy');

-- --------------------------------------------------------------------------
-- 021: Vanessa's own answers (discovery questionnaire, November 2026). Her
-- hero headline, job title, mission and vision, eleven core values,
-- qualifications and memberships, first speaking engagement, story and
-- career change, personal philosophy, the biophilic service in her words
-- with its six benefits, the key messages on the home page, more frequently
-- asked questions, and Africa (including Kenya) as a place she works. Runs
-- once; each change only replaces wording that is still the original (or
-- fills what is empty), so anything edited in the admin is kept.
-- --------------------------------------------------------------------------

-- Interior design is one of her three current services, so visitors can
-- choose it on the contact form.
ALTER TABLE ContactMessages
    MODIFY EnquiryType ENUM('General','Biophilic Design','Colour Psychology','Interior Design','Photography','Workshop','Speaking Engagement','Collaboration','Consultation','Gift Voucher') NOT NULL DEFAULT 'General';

-- Website Settings: job title and where she works.
UPDATE WebsiteSettings SET OwnerTitle = 'Interior Designer – Biophilic & Well-being'
WHERE SettingID = 1 AND (OwnerTitle IS NULL OR OwnerTitle = 'Interior designer')
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE WebsiteSettings SET ServiceAreas = 'Pietermaritzburg and the KwaZulu-Natal Midlands\nOnline consultations across South Africa\nAcross Africa, including Kenya, and further afield by arrangement (travel costs are covered by the client)'
WHERE SettingID = 1 AND ServiceAreas = 'Pietermaritzburg and the KwaZulu-Natal Midlands\nOnline consultations across South Africa\nInternational projects by arrangement (travel costs are covered by the client)'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- Home page: her headline, her three current services, why choose her.
UPDATE HomePageContent SET HeroTitle = 'Designing Spaces that Nurture Mind, Body, and Soul'
WHERE HomePageID = 1 AND HeroTitle = 'Vanessa Talbot'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE HomePageContent SET HeroEyebrow = 'Biophilic & Well-being Design · Colour & Design Psychology · Interior Design'
WHERE HomePageID = 1 AND HeroEyebrow = 'Biophilic Interior Design · Colour & Design Psychology · Photography'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE PageSections SET Body = 'Her knowledge of biophilic design and colour & design psychology, backed by thirty years in science, means every choice is made for a reason. Vanessa listens to your vision for your space and its colours, and transforms it into something tangible, natural and sustainable.'
WHERE PageKey = 'home' AND SectionKey = 'introduction'
  AND Body = 'Her work is built on a simple idea: the spaces we spend our days in shape how we think, feel and connect. By combining biophilic design, the psychology of colour and a photographer''s eye for light and detail, Vanessa designs interiors that are calm, personal and deeply connected to the natural world.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- Home page: the four key messages, after the introduction.
INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'home', 'key-messages', 'Why biophilic design', 'What nature brings to a space', 'Most of us spend most of our lives indoors. Biophilic design brings nature back in, with benefits that go far beyond the way a room looks.', NULL, NULL, NULL, NULL, NULL, 35, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'sustainable', 'Sustainable living & working', 'Spaces that nurture people and protect the planet, with renewable materials, eco-friendly practices and energy-efficient choices.', 'leaf', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'key-messages' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'sensory', 'Sensory enrichment', 'Light, greenery, textures and natural patterns engage all the senses, creating calm, immersive spaces that reduce stress and help you focus.', 'sprout', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'key-messages' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'performance', 'Performance & productivity', 'Nature-inspired spaces support creativity, concentration and collaboration, in workplaces, schools and homes alike.', 'flask', NULL, 30, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'key-messages' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'timeless', 'Timeless aesthetic appeal', 'Organic beauty that never goes out of style, blending modern living with natural elegance.', 'brush', NULL, 40, 'Published'
FROM PageSections s WHERE s.PageKey = 'home' AND s.SectionKey = 'key-messages' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: her story and short bio.
UPDATE AboutContent SET Biography = 'Vanessa Talbot was born in the UK but has lived her whole life in Africa, first in Zimbabwe and then in South Africa. A free-rein childhood of climbing trees, fetching tadpoles from rivers and building forts made her a child of nature, and nature is still where she is happiest, whether hiking or simply sitting outdoors. It is also where her love of colour and design began: in the colours and patterns nature shows in every season.\n\nCuriosity led her first to science, and eventually to design. Vanessa Talbot Interior Designs brings her studies, her experience and her love of nature together, to create spaces that are beautiful, meaningful and good for the people in them.'
WHERE AboutID = 1 AND Biography LIKE 'Vanessa Talbot has always been fascinated by the relationship between people and the places they spend their lives in.%'
  AND Biography LIKE '%Today, Vanessa Talbot Interior Designs brings those threads together.%'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE AboutContent SET ShortBio = 'Vanessa Talbot is an interior designer specialising in biophilic & well-being design and colour & design psychology. She brings thirty years in analytical chemistry and environmental science to creating spaces that support health, well-being and our connection with nature.'
WHERE AboutID = 1 AND (ShortBio IS NULL OR ShortBio = '')
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: the journey from science to design, in her words.
UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'Vanessa studied analytical chemistry and spent thirty years running an environmental testing laboratory, where she achieved SANAS accreditation to ISO 17025 and became director of the laboratory and the company. Science taught her to observe carefully, ask questions and look for evidence.'
WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND i.ItemKey = 'science'
  AND i.Text = 'A background in science taught Vanessa to observe carefully, ask questions and look for evidence. It also gave her a lasting respect for the natural systems that sustain us.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'Personal tragedy led her to let go of the reins and try her wings at something that would fulfil her and feed her creative soul. The more she researched interior design, the more she saw that science and design are intertwined.'
WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND i.ItemKey = 'design'
  AND i.Text = 'Her love of beauty, colour and composition drew her to interior design, where she discovered she could use both sides of her thinking at once.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'A lifelong connection to nature showed her the way: a career as a biophilic designer, bringing together interior design, colour & design psychology and biophilic & well-being design.'
WHERE s.PageKey = 'about' AND s.SectionKey = 'journey' AND i.ItemKey = 'nature'
  AND i.Text = 'Biophilic design and colour & design psychology brought it all together: design choices informed by how people actually respond to their surroundings, expressed with an artist''s eye.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: mission, vision and the impact she wants to make.
INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'about', 'mission', 'Mission & vision', 'Why Vanessa does this work', 'Vanessa believes everyone has the basic human right to live and work in spaces that connect them to nature. The benefits of biophilic design go far beyond aesthetics: they improve physical and mental health, boost cognition and encourage better stewardship of the environment.', NULL, NULL, NULL, NULL, NULL, 25, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'mission', 'Mission', 'To create beautiful, harmonious, safe and functional spaces, bringing in elements of biophilic design in a way that resonates and aligns with each client''s specific needs.', 'leaf', NULL, 10, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'mission' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'vision', 'Vision', 'To co-create spaces that support human health and well-being with clients and stakeholders, in a way that supports our relationship with nature and our environment.', 'eye', NULL, 20, 'Published'
FROM PageSections s WHERE s.PageKey = 'about' AND s.SectionKey = 'mission' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: her personal philosophy beside the biophilic approach (the
-- Stephen Kellert quote stays on the home page).
UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.ItemKey = 'philosophy', i.Title = 'Vanessa Talbot',
    i.Text = 'I strive to live with integrity, pursue lifelong learning, and treat others and nature with compassion, believing that respecting nature and people shapes a fulfilling life.'
WHERE s.PageKey = 'about' AND s.SectionKey = 'quote' AND i.ItemKey = 'kellert'
  AND i.Text = 'Nature remains our magic well: the more we draw from its nourishing waters, the more we sustain the human body, mind and spirit.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE PageSections SET Introduction = 'Biophilia is our innate connection to nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.'
WHERE PageKey = 'about' AND SectionKey = 'philosophy'
  AND Introduction = 'Biophilia is our innate need to connect with nature. Vanessa''s work starts from the belief that this connection is not a luxury. It is essential to how we rest, focus and feel.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: her eleven core values, in her order. Honesty was not one of them,
-- so it is archived (still in the admin) if its wording is unchanged.
UPDATE AboutValues SET Status = 'Archived'
WHERE Title = 'Honesty' AND Description = 'Clear advice, realistic expectations and straightforward communication at every stage.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE AboutValues SET DisplayOrder = 40 WHERE Title = 'Individuality' AND DisplayOrder = 30
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Curiosity', 'A sense of wonder that led from science to design, and keeps every project asking why.', 'eye', 30, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Wellness', 'Spaces that support physical and mental health, rest and recovery.', 'leaf', 60, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Sustainability', 'Local, natural and lasting choices that respect the environment.', 'sprout', 70, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Education', 'Sharing what biophilic design can do, through talks, workshops and writing.', 'pen', 80, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Research', 'Design decisions informed by evidence about how people respond to their surroundings.', 'flask', 90, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Community', 'Working with clients, makers and fellow designers, and spaces that bring people together.', 'group', 100, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO AboutValues (Title, Description, Icon, DisplayOrder, Status)
SELECT 'Nature Connection', 'Reconnecting people with the natural world, wherever they live and work.', 'leaf', 110, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- About: qualifications and professional memberships (only if none have
-- been added yet).
INSERT INTO AboutQualifications (Title, Type, DisplayOrder, Status)
SELECT q.Title, q.Type, q.DisplayOrder, 'Published' FROM (
    SELECT 'Analytical Chemistry' AS Title, 'Qualification' AS Type, 10 AS DisplayOrder
    UNION ALL SELECT 'Postgraduate Diploma in Business Administration', 'Qualification', 20
    UNION ALL SELECT 'Diploma in Interior Design', 'Qualification', 30
    UNION ALL SELECT 'Business, Branding, Marketing and Social Media for Interior Designers', 'Certification', 40
    UNION ALL SELECT 'Colour and Design Psychology', 'Certification', 50
    UNION ALL SELECT 'Biophilic & Well-being Design', 'Certification', 60
    UNION ALL SELECT 'Green Living Biophilic Society, Kenya', 'Membership', 70
    UNION ALL SELECT 'Habitarmonia Academy & Ecosystem', 'Membership', 80
) q
WHERE NOT EXISTS (SELECT 1 FROM AboutQualifications)
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- Speaking: her first engagement.
INSERT IGNORE INTO SpeakingEngagements (Title, Slug, Organiser, Venue, City, EventDate, Status)
SELECT 'Presentation to first-year interior design students', 'kenya-college-of-interior-design-2026', 'Kenya College of Interior Design', 'Kenya College of Interior Design', 'Kenya', '2026-09-30', 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- Services: biophilic design under her name for it, in her words, with the
-- spaces she works in and the benefits to clients.
UPDATE Services SET ServiceName = 'Biophilic & Well-being Design'
WHERE Slug = 'biophilic-design' AND ServiceName = 'Biophilic Design'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE Services SET Lead = 'Biophilia is our innate connection to nature. Biophilic design brings that connection back into the places where we live and work.'
WHERE Slug = 'biophilic-design' AND Lead = 'People are wired to feel better around nature. Biophilic design brings that connection indoors, deliberately and beautifully.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE Services SET FullDescription = 'Reconnect your spaces with nature through biophilic design, integrating natural elements to create healthier, calmer and more restorative living or working spaces for improved well-being.\n\nDrawing on research into how natural light, greenery, texture, water and views affect the way we feel and function, Vanessa designs spaces that calm the mind, lift energy and support everyday well-being. Biophilic design can be brought into a new or existing space at many budget levels.'
WHERE Slug = 'biophilic-design' AND FullDescription = 'Drawing on research into how natural light, greenery, texture, water and views affect the way we feel and function, Vanessa designs spaces that calm the mind, lift energy and support everyday wellbeing. The result is an interior that feels alive, grounded and quietly restorative.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE Services SET IdealFor = 'Homes and residential\nOffices and workplaces\nHealth clinics, gyms and wellness rooms\nSchools and frail care\nEducation'
WHERE Slug = 'biophilic-design' AND IdealFor = 'Residential homes\nWorkspaces\nHealth & wellness centres'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSections (PageKey, SectionKey, Eyebrow, Heading, Introduction, Body, ButtonText, ButtonLink, SecondaryButtonText, SecondaryButtonLink, DisplayOrder, Status)
SELECT 'service-biophilic-design', 'benefits', 'The benefits', 'What biophilic design does for you', 'The benefits of biophilic design go far beyond aesthetics.', NULL, NULL, NULL, NULL, NULL, 15, 'Published'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, b.ItemKey, b.Title, b.Text, b.Icon, NULL, b.DisplayOrder, 'Published'
FROM PageSections s JOIN (
    SELECT 'well-being' AS ItemKey, 'Enhanced well-being and mental health' AS Title, 'Spaces that help you feel calmer, more rested and more like yourself.' AS Text, 'leaf' AS Icon, 10 AS DisplayOrder
    UNION ALL SELECT 'productivity', 'Increased productivity and creativity', 'Natural light, greenery and views that help people think clearly and work well.', 'brush', 20
    UNION ALL SELECT 'stress', 'Stress reduction and improved cognitive function', 'Restorative surroundings that lower stress and support focus and memory.', 'flask', 30
    UNION ALL SELECT 'urban', 'Connection to nature in urban environments', 'A daily connection with the natural world, even in the middle of a city.', 'sprout', 40
    UNION ALL SELECT 'sustainability', 'Sustainability and environmental stewardship', 'Natural, local and lasting materials that respect the environment.', 'eye', 50
    UNION ALL SELECT 'community', 'Community and social interaction', 'Spaces that invite people to gather, connect and feel they belong.', 'group', 60
) b
WHERE s.PageKey = 'service-biophilic-design' AND s.SectionKey = 'benefits'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

-- Frequently asked questions: her definition, budgets in tiers, AI, and
-- working further afield.
UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'Biophilia is our innate connection to nature. Biophilic design brings the natural world into the spaces where we live and work, through light, plants, water, natural materials and organic form, to create healthier, calmer and more restorative spaces.'
WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND i.ItemKey = 'what-is-biophilic'
  AND i.Text = 'Biophilic design brings the natural world into the spaces where we live and work, through light, plants, water, natural materials and organic form, to support health and wellbeing.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

UPDATE PageSectionItems i JOIN PageSections s ON s.SectionID = i.SectionID
SET i.Text = 'Yes. Biophilic design can be brought into a new or existing design at many budget levels, so projects can be planned in tiers, from a few well-chosen changes to a complete redesign. The budget is agreed with you before any work begins.'
WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND i.ItemKey = 'budget'
  AND i.Text = 'Yes. Every project starts with a realistic budget, agreed with you before any work begins.'
  AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'ai', 'Can''t I just ask AI to design my space?', 'AI can suggest ideas, but it can''t stand in your space, see how the light moves through it, or understand how you live and what you need the space to do. Vanessa combines biophilic design and colour & design psychology with a background in science, and turns your vision into something tangible, natural and sustainable.', NULL, NULL, 70, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO PageSectionItems (SectionID, ItemKey, Title, Text, Icon, Link, DisplayOrder, Status)
SELECT s.SectionID, 'where', 'Do you work outside South Africa?', 'Yes. Vanessa works in person in Pietermaritzburg and the KwaZulu-Natal Midlands, online across South Africa, and across Africa, including Kenya, and further afield by arrangement, with travel costs covered by the client.', NULL, NULL, 80, 'Published'
FROM PageSections s WHERE s.PageKey = 'contact' AND s.SectionKey = 'faq' AND NOT EXISTS (SELECT 1 FROM SchemaMigrations WHERE MigrationName = '021_vanessa_questionnaire');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('021_vanessa_questionnaire');

-- --------------------------------------------------------------------------
-- 022: Collaborator cards. The country (shown as a round flag and its name
-- above the card), an email address and phone number (shown only when
-- entered, and only once the person has agreed to be listed), and whether
-- the picture is a photo (fills the frame) or a logo (shown whole).
-- --------------------------------------------------------------------------
ALTER TABLE Collaborators
    ADD COLUMN IF NOT EXISTS Country VARCHAR(10) NULL COMMENT 'Flag code, for example za or gb-eng' AFTER RoleTitle,
    ADD COLUMN IF NOT EXISTS Email VARCHAR(255) NULL AFTER WebsiteLink,
    ADD COLUMN IF NOT EXISTS Phone VARCHAR(50) NULL AFTER Email,
    ADD COLUMN IF NOT EXISTS ImageFit ENUM('Photo','Logo') NOT NULL DEFAULT 'Photo' AFTER LogoAlt;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('022_collaborator_cards');

