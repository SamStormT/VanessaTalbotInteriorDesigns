-- ==========================================================================
-- Vanessa Talbot Interior Designs: database migrations
-- Run after schema.sql. Safe to re-run: every statement is additive.
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
-- 003: Website Settings location defaults. COALESCE keeps any saved value.
-- Phone and email stay out of this public repository: database/setup.php
-- loads them from the private .env file instead.
-- --------------------------------------------------------------------------
UPDATE WebsiteSettings
SET City = COALESCE(City, 'Pietermaritzburg'),
    Province = COALESCE(Province, 'KwaZulu-Natal'),
    Country = COALESCE(Country, 'South Africa')
WHERE SettingID = 1;

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('003_settings_location_defaults');

-- --------------------------------------------------------------------------
-- 004: The six existing services, with the wording already on the site.
-- INSERT IGNORE never replaces a service that has been edited.
-- --------------------------------------------------------------------------
INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Biophilic Design', 'biophilic-design', 'leaf', 'Interiors that reconnect people with nature through light, living plants, natural materials and organic form.', 'People are wired to feel better around nature. Biophilic design brings that connection indoors, deliberately and beautifully.', 'Drawing on research into how natural light, greenery, texture, water and views affect the way we feel and function, Vanessa designs spaces that calm the mind, lift energy and support everyday wellbeing. The result is an interior that feels alive, grounded and quietly restorative.', 'Assessment of light, airflow, views and how the space is used\nNatural materials, textures and organic shapes\nPlanting schemes suited to the space and the people who care for it\nLayouts that support rest, focus and connection', 'Residential homes\nWorkspaces\nHealth & wellness centres', 'Published', 10);

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Colour Psychology', 'colour-psychology', 'palette', 'Colour chosen with purpose, to influence mood, energy and behaviour in the way each space needs.', 'Colour is never just decoration. It shapes how a room feels, and how we feel in it.', 'Vanessa combines an understanding of how colour affects mood and perception with a designer''s eye for harmony and balance. Whether a space needs to soothe, energise, welcome or inspire, the palette is built around the people who use it and what they need it to do.', 'Colour consultation based on how the space is used and who uses it\nConsidered palettes for walls, furnishings and finishes\nGuidance on natural and artificial light and how it changes colour\nBrand-aligned colour for commercial and retail spaces', 'Residential homes\nBoutiques\nWorkspaces', 'Published', 20);

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Photography', 'photography-services', 'camera', 'Nature and interior photography that captures texture, light and atmosphere.', 'Photography is where Vanessa''s eye for detail and love of the natural world meet.', 'From botanical detail and natural landscapes to finished interiors, Vanessa''s photography is about noticing: the way light falls, the pattern in a leaf, the mood of a room. Selected images are available as prints through the photography store, bringing a piece of nature into your own space.', 'Nature and botanical fine-art photography\nPhotography prints available through the online store\nInterior photography that shows a space at its best\nImagery for designers, boutiques and wellness brands', 'Residential homes\nBoutiques\nHealth & wellness centres', 'Published', 30);

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Speaking Engagements', 'speaking-engagements', 'mic', 'Engaging talks on biophilic design, colour psychology and nature-centred wellbeing.', 'Ideas that change how audiences see the spaces they live and work in.', 'Vanessa speaks on the science and the art of designing with nature: why our surroundings matter so much to our wellbeing, and what anyone can do about it. Talks are tailored to the audience, whether a conference, a corporate wellness day, a community group or an industry event.', 'Keynotes and talks tailored to your audience\nTopics spanning biophilic design, colour and wellbeing\nPractical takeaways audiences can use straight away\nPanel discussions and question-and-answer sessions', 'Events\nWorkspaces\nHealth & wellness centres', 'Published', 40);

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Workshops', 'workshops', 'group', 'Hands-on, interactive sessions that teach people to bring nature and colour into their own spaces.', 'Learn by doing, and leave with ideas you can put into practice at home or at work.', 'Vanessa''s workshops turn the principles of biophilic design and colour psychology into practical, enjoyable experiences. Participants explore how their surroundings affect them, then work through simple, achievable changes. Sessions suit teams, wellness programmes, retail events and community groups.', 'Interactive group sessions with hands-on activities\nPractical guides and worksheets to take home\nContent tailored to teams, clients or communities\nOptions for corporate wellness and team-building days', 'Workspaces\nEvents\nHealth & wellness centres', 'Published', 50);

INSERT IGNORE INTO Services (ServiceName, Slug, Icon, ShortDescription, Lead, FullDescription, IncludedItems, IdealFor, Status, DisplayOrder)
VALUES ('Consultations', 'consultations', 'chat', 'One-on-one guidance to help you understand your space and plan meaningful change.', 'Not every space needs a full redesign. Sometimes the right advice changes everything.', 'A consultation is a focused session to look at your space together, understand how you use it and how you want it to feel, and agree on clear next steps. It is the ideal starting point for any project, and can stand alone for clients who want expert direction to carry out themselves.', 'A walk-through and discussion of your space and goals\nBiophilic and colour recommendations specific to you\nClear, prioritised next steps\nA starting point for a larger design project, if you choose', 'Residential homes\nWorkspaces\nBoutiques\nHealth & wellness centres', 'Published', 60);

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('004_seed_services');
