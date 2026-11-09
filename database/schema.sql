-- ==========================================================================
-- Vanessa Talbot Interior Designs: database schema (29 tables)
-- Target: MariaDB 10.2.1 or newer (Xneelo Standard Hosting)
--
-- SAFE TO RE-RUN. This file never drops, truncates or overwrites anything:
--   * every table uses CREATE TABLE IF NOT EXISTS
--   * seed rows use INSERT IGNORE
-- Later structure changes belong in database/migrations.sql, recorded in
-- SchemaMigrations, because CREATE TABLE IF NOT EXISTS will not alter a table
-- that already exists.
--
-- Create the database itself in the hosting control panel, select it, then
-- import this file. There is deliberately no CREATE DATABASE or USE here.
--
-- There is deliberately no default admin account: the repository is public,
-- so the first SuperAdmin must be created on the server, never from this file.
--
-- Conventions: InnoDB, utf8mb4, money as DECIMAL(10,2) in ZAR, dates in SAST.
-- utf8mb4_unicode_ci makes email uniqueness case-insensitive.
-- Public content uses Status: Draft, Published, Archived.
-- Columns described as "one per line" are edited as a simple text box.
-- ==========================================================================

SET NAMES utf8mb4;

-- ==========================================================================
-- STAFF AND SECURITY
-- ==========================================================================

-- --------------------------------------------------------------------------
-- AdminUsers
-- Deactivate staff (Status = 'Inactive') rather than deleting them, so that
-- the posts and campaigns they authored keep their history.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AdminUsers (
    AdminUserID         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name                VARCHAR(100) NOT NULL,
    Surname             VARCHAR(100) NOT NULL,
    Email               VARCHAR(254) NOT NULL,
    PasswordHash        VARCHAR(255) NOT NULL COMMENT 'password_hash() output, never plain text',
    Role                ENUM('SuperAdmin','Admin','Editor') NOT NULL DEFAULT 'Editor',
    Status              ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    CreatedDate         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    LastLogin           DATETIME NULL,
    PasswordChangedDate DATETIME NULL COMMENT 'Sessions older than this are ended',
    PRIMARY KEY (AdminUserID),
    UNIQUE KEY uq_AdminUsers_Email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PasswordResets
-- Only a hash of the emailed token is stored, so a leaked database cannot be
-- used to reset passwords.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PasswordResets (
    ResetID     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    AdminUserID INT UNSIGNED NOT NULL,
    TokenHash   CHAR(64) NOT NULL COMMENT 'SHA-256 of the emailed token',
    ExpiryDate  DATETIME NOT NULL,
    UsedDate    DATETIME NULL,
    RequestIP   VARCHAR(45) NULL,
    CreatedDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ResetID),
    UNIQUE KEY uq_PasswordResets_TokenHash (TokenHash),
    KEY idx_PasswordResets_User (AdminUserID, ExpiryDate),
    CONSTRAINT fk_PasswordResets_User FOREIGN KEY (AdminUserID)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- LoginAttempts (rate limiting; no foreign key, because attempts can name
-- addresses that do not belong to any account)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS LoginAttempts (
    AttemptID   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Email       VARCHAR(254) NOT NULL,
    IPAddress   VARCHAR(45) NOT NULL,
    Successful  TINYINT(1) NOT NULL DEFAULT 0,
    AttemptDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (AttemptID),
    KEY idx_LoginAttempts_Email (Email, AttemptDate),
    KEY idx_LoginAttempts_IP (IPAddress, AttemptDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- ActivityLog (who changed what; survives the removal of the user)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ActivityLog (
    LogID       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    AdminUserID INT UNSIGNED NULL,
    Action      VARCHAR(50) NOT NULL COMMENT 'For example create, update, publish, delete, login',
    EntityType  VARCHAR(50) NULL COMMENT 'For example BlogPosts',
    EntityID    BIGINT UNSIGNED NULL,
    Details     TEXT NULL,
    IPAddress   VARCHAR(45) NULL,
    CreatedDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (LogID),
    KEY idx_ActivityLog_User (AdminUserID, CreatedDate),
    KEY idx_ActivityLog_Entity (EntityType, EntityID),
    KEY idx_ActivityLog_Date (CreatedDate),
    CONSTRAINT fk_ActivityLog_User FOREIGN KEY (AdminUserID)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- SchemaMigrations (which structure changes have run on this server)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SchemaMigrations (
    MigrationID   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    MigrationName VARCHAR(150) NOT NULL,
    AppliedDate   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MigrationID),
    UNIQUE KEY uq_SchemaMigrations_Name (MigrationName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- SITE-WIDE CONTENT
-- ==========================================================================

-- --------------------------------------------------------------------------
-- WebsiteSettings (single row, SettingID = 1)
-- Never store SMTP passwords or PayFast keys here: they stay in a private
-- config file outside the web root.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS WebsiteSettings (
    SettingID           INT UNSIGNED NOT NULL DEFAULT 1,
    BusinessName        VARCHAR(150) NOT NULL,
    Tagline             VARCHAR(255) NULL,
    Phone               VARCHAR(30)  NULL,
    WhatsApp            VARCHAR(30)  NULL,
    Email               VARCHAR(254) NULL,
    EnquiryEmail        VARCHAR(254) NULL COMMENT 'Where contact-form messages are sent',
    AddressLine1        VARCHAR(150) NULL,
    AddressLine2        VARCHAR(150) NULL,
    City                VARCHAR(100) NULL,
    Province            VARCHAR(100) NULL,
    PostalCode          VARCHAR(10)  NULL,
    Country             VARCHAR(100) NULL,
    ShowAddress         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Street address is private unless switched on',
    RegistrationNumber  VARCHAR(50)  NULL,
    VATNumber           VARCHAR(30)  NULL,
    Facebook            VARCHAR(500) NULL,
    Instagram           VARCHAR(500) NULL,
    LinkedIn            VARCHAR(500) NULL,
    Pinterest           VARCHAR(500) NULL,
    YouTube             VARCHAR(500) NULL,
    TikTok              VARCHAR(500) NULL,
    FooterCopyright     VARCHAR(255) NULL,
    GoogleAnalyticsCode VARCHAR(50)  NULL COMMENT 'Measurement ID only (G-XXXXXXX), never script markup',
    UpdatedDate         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (SettingID),
    CONSTRAINT chk_WebsiteSettings_SingleRow CHECK (SettingID = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- HomePageContent (single row, HomePageID = 1)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS HomePageContent (
    HomePageID           INT UNSIGNED NOT NULL DEFAULT 1,
    HeroEyebrow          VARCHAR(255) NULL,
    HeroTitle            VARCHAR(255) NULL,
    HeroSubtitle         VARCHAR(500) NULL,
    HeroImage            VARCHAR(500) NULL,
    HeroImageAlt         VARCHAR(255) NULL,
    IntroductionText     TEXT NULL,
    IntroductionImage    VARCHAR(500) NULL,
    IntroductionImageAlt VARCHAR(255) NULL,
    FeaturedQuote        TEXT NULL,
    FeaturedQuoteAuthor  VARCHAR(150) NULL,
    CTA1Text             VARCHAR(100) NULL,
    CTA1Link             VARCHAR(500) NULL,
    CTA2Text             VARCHAR(100) NULL,
    CTA2Link             VARCHAR(500) NULL,
    FeaturedCTATitle     VARCHAR(255) NULL,
    FeaturedCTAText      TEXT NULL,
    FeaturedCTALink      VARCHAR(500) NULL,
    MetaTitle            VARCHAR(255) NULL,
    MetaDescription      VARCHAR(500) NULL,
    UpdatedDate          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (HomePageID),
    CONSTRAINT chk_HomePageContent_SingleRow CHECK (HomePageID = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- AboutContent (single row, AboutID = 1)
-- CoreValues and Qualifications hold each section's introduction. The
-- individual items are rows in AboutValues and AboutQualifications.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AboutContent (
    AboutID              INT UNSIGNED NOT NULL DEFAULT 1,
    Biography            MEDIUMTEXT NULL,
    Journey              MEDIUMTEXT NULL,
    ScienceBackground    MEDIUMTEXT NULL,
    DesignBackground     MEDIUMTEXT NULL,
    Philosophy           MEDIUMTEXT NULL,
    CoreValues           MEDIUMTEXT NULL,
    Qualifications       MEDIUMTEXT NULL,
    SpeakingIntroduction TEXT NULL,
    SpeakingTopics       TEXT NULL COMMENT 'One per line',
    PortraitImage        VARCHAR(500) NULL,
    PortraitImageAlt     VARCHAR(255) NULL,
    MetaTitle            VARCHAR(255) NULL,
    MetaDescription      VARCHAR(500) NULL,
    UpdatedDate          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (AboutID),
    CONSTRAINT chk_AboutContent_SingleRow CHECK (AboutID = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- AboutValues (Authenticity, Creativity and so on)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AboutValues (
    ValueID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Title        VARCHAR(100) NOT NULL,
    Description  TEXT NULL,
    Icon         VARCHAR(50) NULL,
    DisplayOrder INT NOT NULL DEFAULT 0,
    Status       ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    UpdatedDate  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ValueID),
    UNIQUE KEY uq_AboutValues_Title (Title),
    KEY idx_AboutValues_Listing (Status, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- AboutQualifications (qualifications, certifications, memberships, awards)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS AboutQualifications (
    QualificationID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Title           VARCHAR(255) NOT NULL,
    Type            ENUM('Qualification','Certification','Membership','Award') NOT NULL DEFAULT 'Qualification',
    Institution     VARCHAR(255) NULL,
    FieldOfStudy    VARCHAR(255) NULL,
    YearCompleted   SMALLINT UNSIGNED NULL,
    ExpiryDate      DATE NULL,
    DisplayOrder    INT NOT NULL DEFAULT 0,
    Status          ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    UpdatedDate     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (QualificationID),
    KEY idx_AboutQualifications_Listing (Status, Type, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- SitePages (Privacy Policy, Terms and Conditions, Copyright, future pages)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SitePages (
    PageID          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Slug            VARCHAR(200) NOT NULL,
    Title           VARCHAR(255) NOT NULL,
    Content         MEDIUMTEXT NULL,
    MetaTitle       VARCHAR(255) NULL,
    MetaDescription VARCHAR(500) NULL,
    Status          ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    UpdatedDate     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (PageID),
    UNIQUE KEY uq_SitePages_Slug (Slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- SERVICES, PORTFOLIO, SPEAKING, TESTIMONIALS
-- ==========================================================================

-- --------------------------------------------------------------------------
-- Services
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Services (
    ServiceID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ServiceName      VARCHAR(150) NOT NULL,
    Slug             VARCHAR(200) NOT NULL,
    Icon             VARCHAR(50)  NULL,
    ShortDescription VARCHAR(500) NULL,
    Lead             VARCHAR(500) NULL,
    FullDescription  MEDIUMTEXT NULL,
    IncludedItems    TEXT NULL COMMENT 'What is included, one per line',
    IdealFor         TEXT NULL COMMENT 'Client types, one per line',
    FeaturedImage    VARCHAR(500) NULL,
    FeaturedImageAlt VARCHAR(255) NULL,
    MetaTitle        VARCHAR(255) NULL,
    MetaDescription  VARCHAR(500) NULL,
    Status           ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    DisplayOrder     INT NOT NULL DEFAULT 0,
    UpdatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ServiceID),
    UNIQUE KEY uq_Services_ServiceName (ServiceName),
    UNIQUE KEY uq_Services_Slug (Slug),
    KEY idx_Services_Listing (Status, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PortfolioProjects
-- PortfolioCategory matches the portfolio pages. ProjectType is the kind of
-- client or work. ClientPermission must be set before publishing.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PortfolioProjects (
    ProjectID         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Title             VARCHAR(255) NOT NULL,
    Slug              VARCHAR(200) NOT NULL,
    PortfolioCategory ENUM('Biophilic Interior Design','Colour Psychology','Photography Gallery') NOT NULL,
    ProjectType       ENUM('Residential','Commercial','Concept','Personal','Photography') NOT NULL,
    Location          VARCHAR(150) NULL,
    Description       MEDIUMTEXT NULL,
    FeaturedImage     VARCHAR(500) NULL,
    FeaturedImageAlt  VARCHAR(255) NULL,
    IsFeatured        TINYINT(1) NOT NULL DEFAULT 0,
    DisplayOrder      INT NOT NULL DEFAULT 0,
    ClientPermission  TINYINT(1) NOT NULL DEFAULT 0,
    MetaTitle         VARCHAR(255) NULL,
    MetaDescription   VARCHAR(500) NULL,
    Status            ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (ProjectID),
    UNIQUE KEY uq_PortfolioProjects_Slug (Slug),
    KEY idx_PortfolioProjects_Listing (Status, PortfolioCategory, DisplayOrder),
    KEY idx_PortfolioProjects_Type (Status, ProjectType),
    KEY idx_PortfolioProjects_Featured (Status, IsFeatured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- ProjectImages (many per project; removed with their project)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ProjectImages (
    ImageID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ProjectID    INT UNSIGNED NOT NULL,
    ImagePath    VARCHAR(500) NOT NULL,
    AltText      VARCHAR(255) NULL,
    Caption      VARCHAR(500) NULL,
    DisplayOrder INT NOT NULL DEFAULT 0,
    PRIMARY KEY (ImageID),
    KEY idx_ProjectImages_Project (ProjectID, DisplayOrder),
    CONSTRAINT fk_ProjectImages_Project FOREIGN KEY (ProjectID)
        REFERENCES PortfolioProjects (ProjectID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Collaborators
-- ConsentDate records their permission to be listed.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Collaborators (
    CollaboratorID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name           VARCHAR(200) NOT NULL,
    Slug           VARCHAR(200) NOT NULL,
    RoleTitle      VARCHAR(150) NULL COMMENT 'What they do, for example Landscaper',
    Description    TEXT NULL,
    Logo           VARCHAR(500) NULL,
    LogoAlt        VARCHAR(255) NULL,
    WebsiteLink    VARCHAR(500) NULL,
    ConsentDate    DATE NULL,
    DisplayOrder   INT NOT NULL DEFAULT 0,
    Status         ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (CollaboratorID),
    UNIQUE KEY uq_Collaborators_Slug (Slug),
    KEY idx_Collaborators_Listing (Status, DisplayOrder)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- Testimonials
-- ConsentDate records the client's written permission (POPIA). The admin
-- screen must refuse to publish without it.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Testimonials (
    TestimonialID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name          VARCHAR(150) NOT NULL,
    Company       VARCHAR(150) NULL,
    Location      VARCHAR(150) NULL,
    Testimonial   TEXT NOT NULL,
    Category      VARCHAR(100) NULL,
    ProjectID     INT UNSIGNED NULL,
    Photo         VARCHAR(500) NULL,
    PhotoAlt      VARCHAR(255) NULL,
    IsFeatured    TINYINT(1) NOT NULL DEFAULT 0,
    ConsentDate   DATE NULL,
    DisplayOrder  INT NOT NULL DEFAULT 0,
    Status        ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (TestimonialID),
    KEY idx_Testimonials_Listing (Status, DisplayOrder),
    KEY idx_Testimonials_Project (ProjectID),
    CONSTRAINT fk_Testimonials_Project FOREIGN KEY (ProjectID)
        REFERENCES PortfolioProjects (ProjectID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- SpeakingEngagements
-- Upcoming or past is worked out from EventDate, so it can never go stale.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS SpeakingEngagements (
    EngagementID     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Title            VARCHAR(255) NOT NULL,
    Slug             VARCHAR(200) NOT NULL,
    Organiser        VARCHAR(255) NULL,
    Venue            VARCHAR(255) NULL,
    City             VARCHAR(100) NULL,
    EventDate        DATE NULL,
    EventLink        VARCHAR(500) NULL,
    Description      MEDIUMTEXT NULL,
    FeaturedImage    VARCHAR(500) NULL,
    FeaturedImageAlt VARCHAR(255) NULL,
    Status           ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (EngagementID),
    UNIQUE KEY uq_SpeakingEngagements_Slug (Slug),
    KEY idx_SpeakingEngagements_Listing (Status, EventDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- PHOTOGRAPHY STORE
-- ==========================================================================

-- --------------------------------------------------------------------------
-- PhotographyProducts
-- DownloadFile must point outside the web root, or paid files become free.
-- A product with orders cannot be deleted: archive it instead.
-- Price is the base price; print sizes can override it in the options table.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PhotographyProducts (
    PhotoID      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Title        VARCHAR(255) NOT NULL,
    Slug         VARCHAR(200) NOT NULL,
    ProductType  ENUM('Digital','Print','Both') NOT NULL DEFAULT 'Print',
    Description  MEDIUMTEXT NULL,
    Category     VARCHAR(100) NULL,
    Price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PreviewImage VARCHAR(500) NULL,
    AltText      VARCHAR(255) NULL,
    DownloadFile VARCHAR(500) NULL COMMENT 'Private path outside the web root',
    Status       ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (PhotoID),
    UNIQUE KEY uq_PhotographyProducts_Slug (Slug),
    KEY idx_PhotographyProducts_Listing (Status, Category),
    CONSTRAINT chk_PhotographyProducts_Price CHECK (Price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PhotographyProductOptions (print sizes, materials and framing)
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PhotographyProductOptions (
    OptionID     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    PhotoID      INT UNSIGNED NOT NULL,
    Label        VARCHAR(150) NOT NULL COMMENT 'For example A3 fine-art paper, framed',
    Size         VARCHAR(50)  NULL,
    Material     VARCHAR(100) NULL,
    IsFramed     TINYINT(1) NOT NULL DEFAULT 0,
    Price        DECIMAL(10,2) NOT NULL,
    DisplayOrder INT NOT NULL DEFAULT 0,
    Status       ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    PRIMARY KEY (OptionID),
    KEY idx_ProductOptions_Photo (PhotoID, Status, DisplayOrder),
    CONSTRAINT fk_ProductOptions_Photo FOREIGN KEY (PhotoID)
        REFERENCES PhotographyProducts (PhotoID) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_ProductOptions_Price CHECK (Price >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PhotographyOrders (one per checkout; the products are in the items table)
-- Amount is the full order total at purchase, so later price changes never
-- alter history. OrderNumber is the non-guessable public reference.
-- PaymentReference is unique so a payment cannot be applied to two orders.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PhotographyOrders (
    OrderID          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    OrderNumber      VARCHAR(32) NOT NULL,
    CustomerName     VARCHAR(200) NOT NULL,
    CustomerEmail    VARCHAR(254) NOT NULL,
    CustomerPhone    VARCHAR(30) NULL,
    ShippingAddressLine1 VARCHAR(150) NULL,
    ShippingAddressLine2 VARCHAR(150) NULL,
    ShippingCity     VARCHAR(100) NULL,
    ShippingProvince VARCHAR(100) NULL,
    ShippingPostalCode VARCHAR(10) NULL,
    ShippingCountry  VARCHAR(100) NULL,
    ShippingCost     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    DeliveryStatus   ENUM('NotRequired','Pending','Shipped','Delivered','Collected') NOT NULL DEFAULT 'NotRequired',
    TrackingNumber   VARCHAR(100) NULL,
    Amount           DECIMAL(10,2) NOT NULL,
    OrderStatus      ENUM('Pending','Paid','Failed','Refunded','Cancelled') NOT NULL DEFAULT 'Pending',
    PaymentReference VARCHAR(100) NULL,
    DownloadToken    CHAR(64) NULL COMMENT 'SHA-256 of the emailed download token',
    DownloadExpiry   DATETIME NULL,
    PurchaseDate     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (OrderID),
    UNIQUE KEY uq_PhotographyOrders_OrderNumber (OrderNumber),
    UNIQUE KEY uq_PhotographyOrders_PaymentReference (PaymentReference),
    UNIQUE KEY uq_PhotographyOrders_DownloadToken (DownloadToken),
    KEY idx_PhotographyOrders_Status (OrderStatus, PurchaseDate),
    KEY idx_PhotographyOrders_Delivery (DeliveryStatus),
    KEY idx_PhotographyOrders_Email (CustomerEmail),
    CONSTRAINT chk_PhotographyOrders_Amount CHECK (Amount >= 0),
    CONSTRAINT chk_PhotographyOrders_Shipping CHECK (ShippingCost >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PhotographyOrderItems (cart lines)
-- ItemTitle, OptionLabel and UnitPrice are copies taken at purchase, so an
-- order still reads correctly after the product changes.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PhotographyOrderItems (
    OrderItemID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    OrderID     INT UNSIGNED NOT NULL,
    PhotoID     INT UNSIGNED NOT NULL,
    OptionID    INT UNSIGNED NULL,
    ItemTitle   VARCHAR(255) NOT NULL,
    OptionLabel VARCHAR(150) NULL,
    UnitPrice   DECIMAL(10,2) NOT NULL,
    Quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (OrderItemID),
    KEY idx_OrderItems_Order (OrderID),
    KEY idx_OrderItems_Photo (PhotoID),
    KEY idx_OrderItems_Option (OptionID),
    CONSTRAINT fk_OrderItems_Order FOREIGN KEY (OrderID)
        REFERENCES PhotographyOrders (OrderID) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_OrderItems_Photo FOREIGN KEY (PhotoID)
        REFERENCES PhotographyProducts (PhotoID) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_OrderItems_Option FOREIGN KEY (OptionID)
        REFERENCES PhotographyProductOptions (OptionID) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_OrderItems_UnitPrice CHECK (UnitPrice >= 0),
    CONSTRAINT chk_OrderItems_Quantity CHECK (Quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- PaymentTransactions (append-only financial record)
-- One row per gateway reference and status, so a repeated payment
-- notification cannot be recorded twice.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS PaymentTransactions (
    TransactionID    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    OrderID          INT UNSIGNED NOT NULL,
    GatewayReference VARCHAR(100) NOT NULL,
    Amount           DECIMAL(10,2) NOT NULL,
    Fee              DECIMAL(10,2) NULL,
    PaymentMethod    VARCHAR(50) NULL,
    Status           ENUM('Pending','Complete','Failed','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
    RawResponse      MEDIUMTEXT NULL COMMENT 'Gateway notification, without any secrets',
    TransactionDate  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (TransactionID),
    UNIQUE KEY uq_PaymentTransactions_Notification (GatewayReference, Status),
    KEY idx_PaymentTransactions_Order (OrderID),
    CONSTRAINT fk_PaymentTransactions_Order FOREIGN KEY (OrderID)
        REFERENCES PhotographyOrders (OrderID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- AUDIENCE
-- ==========================================================================

-- --------------------------------------------------------------------------
-- Subscribers
-- Unique Email supports the contact-form rule (see CMS_ARCHITECTURE.md).
-- POPIA: ConsentDate and ConsentIP prove the opt-in, UnsubscribeToken powers
-- the opt-out link, and campaigns only go to rows with a ConfirmedDate.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS Subscribers (
    SubscriberID       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name               VARCHAR(100) NULL,
    Surname            VARCHAR(100) NULL,
    Email              VARCHAR(254) NOT NULL,
    SubscriptionStatus ENUM('Subscribed','Unsubscribed') NOT NULL DEFAULT 'Subscribed',
    Source             ENUM('Newsletter Popup','Footer Subscribe','Homepage Section','Contact Form','Admin Added','Import') NOT NULL,
    ConsentDate        DATETIME NULL,
    ConsentIP          VARCHAR(45) NULL,
    ConfirmedDate      DATETIME NULL COMMENT 'Set when the confirmation link is clicked',
    UnsubscribeToken   CHAR(64) NOT NULL,
    SubscribedDate     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UnsubscribedDate   DATETIME NULL,
    UpdatedDate        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Notes              TEXT NULL,
    PRIMARY KEY (SubscriberID),
    UNIQUE KEY uq_Subscribers_Email (Email),
    UNIQUE KEY uq_Subscribers_UnsubscribeToken (UnsubscribeToken),
    KEY idx_Subscribers_Status (SubscriptionStatus, ConfirmedDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- NewsletterCampaigns
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS NewsletterCampaigns (
    CampaignID     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    CampaignName   VARCHAR(255) NOT NULL,
    Subject        VARCHAR(255) NOT NULL,
    Content        MEDIUMTEXT NULL,
    CreatedBy      INT UNSIGNED NOT NULL,
    CreatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ScheduledDate  DATETIME NULL,
    SentDate       DATETIME NULL,
    RecipientCount INT UNSIGNED NOT NULL DEFAULT 0,
    Status         ENUM('Draft','Scheduled','Sent','Archived') NOT NULL DEFAULT 'Draft',
    PRIMARY KEY (CampaignID),
    KEY idx_NewsletterCampaigns_CreatedBy (CreatedBy),
    KEY idx_NewsletterCampaigns_Status (Status, ScheduledDate),
    CONSTRAINT fk_NewsletterCampaigns_CreatedBy FOREIGN KEY (CreatedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- CampaignRecipients
-- One row per person per campaign, so an interrupted send can resume without
-- emailing anyone twice. Erasing a subscriber (POPIA) removes their rows.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS CampaignRecipients (
    RecipientID  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    CampaignID   INT UNSIGNED NOT NULL,
    SubscriberID INT UNSIGNED NOT NULL,
    Status       ENUM('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
    SentDate     DATETIME NULL,
    ErrorMessage VARCHAR(500) NULL,
    PRIMARY KEY (RecipientID),
    UNIQUE KEY uq_CampaignRecipients_Person (CampaignID, SubscriberID),
    KEY idx_CampaignRecipients_Queue (CampaignID, Status),
    KEY idx_CampaignRecipients_Subscriber (SubscriberID),
    CONSTRAINT fk_CampaignRecipients_Campaign FOREIGN KEY (CampaignID)
        REFERENCES NewsletterCampaigns (CampaignID) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_CampaignRecipients_Subscriber FOREIGN KEY (SubscriberID)
        REFERENCES Subscribers (SubscriberID) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- ContactMessages
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ContactMessages (
    MessageID             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name                  VARCHAR(100) NOT NULL,
    Surname               VARCHAR(100) NULL,
    Email                 VARCHAR(254) NOT NULL,
    Phone                 VARCHAR(30)  NULL,
    Subject               VARCHAR(255) NULL,
    Message               TEXT NOT NULL,
    EnquiryType           ENUM('General','Biophilic Design','Colour Psychology','Photography','Workshop','Speaking Engagement','Collaboration','Consultation') NOT NULL DEFAULT 'General',
    SubscribeToNewsletter TINYINT(1) NOT NULL DEFAULT 0,
    Status                ENUM('New','InProgress','Responded','Closed') NOT NULL DEFAULT 'New',
    InternalNotes         TEXT NULL,
    IPAddress             VARCHAR(45) NULL,
    CreatedDate           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    RespondedDate         DATETIME NULL,
    AssignedTo            INT UNSIGNED NULL,
    PRIMARY KEY (MessageID),
    KEY idx_ContactMessages_Status (Status, CreatedDate),
    KEY idx_ContactMessages_Email (Email),
    KEY idx_ContactMessages_AssignedTo (AssignedTo),
    CONSTRAINT fk_ContactMessages_AssignedTo FOREIGN KEY (AssignedTo)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- BLOG AND MEDIA
-- ==========================================================================

-- --------------------------------------------------------------------------
-- BlogCategories
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS BlogCategories (
    CategoryID   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    CategoryName VARCHAR(100) NOT NULL,
    Slug         VARCHAR(200) NOT NULL,
    Description  TEXT NULL,
    PRIMARY KEY (CategoryID),
    UNIQUE KEY uq_BlogCategories_CategoryName (CategoryName),
    UNIQUE KEY uq_BlogCategories_Slug (Slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- BlogPosts
-- Deleting a category leaves its posts in place, uncategorised.
-- The FULLTEXT index serves blog/search.php.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS BlogPosts (
    BlogID           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    AuthorID         INT UNSIGNED NOT NULL,
    Title            VARCHAR(255) NOT NULL,
    Excerpt          TEXT NULL,
    Content          MEDIUMTEXT NULL,
    FeaturedImage    VARCHAR(500) NULL,
    FeaturedImageAlt VARCHAR(255) NULL,
    CategoryID       INT UNSIGNED NULL,
    IsFeatured       TINYINT(1) NOT NULL DEFAULT 0,
    Status           ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    CreatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PublishedDate    DATETIME NULL,
    Slug             VARCHAR(200) NOT NULL,
    MetaTitle        VARCHAR(255) NULL,
    MetaDescription  VARCHAR(500) NULL,
    PRIMARY KEY (BlogID),
    UNIQUE KEY uq_BlogPosts_Slug (Slug),
    KEY idx_BlogPosts_Author (AuthorID),
    KEY idx_BlogPosts_Category (CategoryID),
    KEY idx_BlogPosts_Listing (Status, PublishedDate),
    FULLTEXT KEY ft_BlogPosts_Search (Title, Excerpt, Content),
    CONSTRAINT fk_BlogPosts_Author FOREIGN KEY (AuthorID)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_BlogPosts_Category FOREIGN KEY (CategoryID)
        REFERENCES BlogCategories (CategoryID) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------------
-- MediaLibrary
-- The database stores the path; the file itself lives in /uploads, which
-- deployments must never overwrite or delete.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS MediaLibrary (
    MediaID    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    FileName   VARCHAR(255) NOT NULL,
    FilePath   VARCHAR(500) NOT NULL,
    FileType   VARCHAR(100) NOT NULL,
    FileSize   INT UNSIGNED NULL COMMENT 'Bytes',
    Width      INT UNSIGNED NULL,
    Height     INT UNSIGNED NULL,
    AltText    VARCHAR(255) NULL,
    Category   VARCHAR(100) NULL,
    UploadedBy INT UNSIGNED NOT NULL,
    UploadDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MediaID),
    UNIQUE KEY uq_MediaLibrary_FilePath (FilePath),
    KEY idx_MediaLibrary_Category (Category, UploadDate),
    KEY idx_MediaLibrary_UploadedBy (UploadedBy),
    CONSTRAINT fk_MediaLibrary_UploadedBy FOREIGN KEY (UploadedBy)
        REFERENCES AdminUsers (AdminUserID) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- SEED DATA
-- Rows the admin screens need to exist. INSERT IGNORE leaves existing
-- content untouched on re-runs. Contact details stay empty until confirmed.
-- ==========================================================================
INSERT IGNORE INTO WebsiteSettings (SettingID, BusinessName, Tagline, Country)
VALUES (1, 'Vanessa Talbot Interior Designs', 'The Balance of Art, Science and Nature in Spaces', 'South Africa');

INSERT IGNORE INTO HomePageContent (HomePageID) VALUES (1);

INSERT IGNORE INTO AboutContent (AboutID) VALUES (1);

INSERT IGNORE INTO SitePages (Slug, Title) VALUES
    ('privacy-policy', 'Privacy Policy'),
    ('terms-and-conditions', 'Terms and Conditions'),
    ('copyright', 'Copyright');

INSERT IGNORE INTO SchemaMigrations (MigrationName) VALUES ('001_initial_schema');
