# Phase 5 Implementation Plan

**Status: proposed, awaiting approval. No Phase 5 code has been written.**

This lists everything still outstanding after Phase 4, in build order. Permissions follow the matrix in `database/CMS_ARCHITECTURE.md`.

**Complexity**

- **Low:** one simple screen or page, using what already exists.
- **Medium:** several screens or a new pattern, with moderate validation.
- **High:** files, money, email delivery, or security-critical logic.

## 1. What already exists

| Area | Built |
|---|---|
| Database | 29 tables, migrations, command-line setup |
| Security | Sign-in, sessions, CSRF, roles, login limits, activity log, password reset logic (no screen) |
| Admin | Dashboard, Website Settings, Homepage, About, Services list and edit |
| Public | Home, About and Services pages, using fixed text |
| Placeholders only | All other admin screens; public contact, speaking, portfolio, blog, photography, service detail and legal pages |

## 2. Blockers outside the code

| Needed | From | Holds up |
|---|---|---|
| Xneelo: command-line access, MariaDB version, cron, outbound email limits | Samantha | D1, D5, E7, launch |
| Composer installed locally, or PHPMailer added by hand | Samantha | D1 |
| GD extension switched on in `php.ini` | Samantha | A3 |
| Photos, logo files, licensed web fonts | Vanessa | C2 onwards (site works without them, with decorative panels) |
| Confirmed website wording, qualifications, testimonials with written permission | Vanessa | B2, B4, C2, C3 |
| Legal wording, Information Officer name, business registration and VAT details | Vanessa | B3, E5, launch |
| Prints, downloads or both; sizes, prices, delivery terms | Vanessa | E1 to E8 |
| PayFast merchant account (sandbox is enough to build) | Vanessa | E6 |
| Which mailbox receives enquiries; newsletter frequency | Vanessa | D2, D5 |

## 3. Stage A: Admin foundations

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| A1 | CMS list and record framework: create, archive, permanent delete, reorder, search, pagination, plus new permissions (`about_items`, `site_pages`, `collaborators`, `blog_categories`, `product_options`, `campaigns`) | Medium | Phase 4 | All content tables, `ActivityLog` | None directly; every later module uses it |
| A2 | My Account: change own password and details | Low | Phase 4 | `AdminUsers`, `ActivityLog` | None |
| A3 | Media Library: validated uploads, resized copies, alt text, usage check before delete, PHP blocked in `uploads/` | High | A1, GD | `MediaLibrary` and every table with an image path | None directly; supplies all site images |
| A4 | Users: create, edit, role, deactivate, recent login attempts, last-SuperAdmin protection | Medium | A1 | `AdminUsers`, `LoginAttempts`, `ActivityLog` | None |

## 4. Stage B: Content management

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| B1 | Services: complete with create and archive, image picker | Low | A1, A3 | `Services` | Services page and home cards |
| B2 | About values and qualifications panels | Low | A1 | `AboutValues`, `AboutQualifications` | About page |
| B3 | Site Pages: legal and other pages | Low | A1 | `SitePages` | Privacy, Terms, Copyright |
| B4 | Testimonials, with consent required to publish | Low | A1, A3 | `Testimonials`, `PortfolioProjects` | Testimonial displays |
| B5 | Speaking Engagements | Low | A1, A3 | `SpeakingEngagements` | Speaking page, About page |
| B6 | Collaborators, with consent required to publish | Low | A1, A3 | `Collaborators` | Collaborators page |
| B7 | Portfolio Projects and gallery images, permission required to publish | Medium | A1, A3 | `PortfolioProjects`, `ProjectImages` | Portfolio pages, home featured section |
| B8 | Blog Categories | Low | A1 | `BlogCategories` | Blog category pages |
| B9 | Blog Posts: editor, slug generator, preview, scheduling by date | Medium | A1, A3, B8 | `BlogPosts`, `AdminUsers` | Blog, home latest posts |
| B10 | Activity Log screen with filters | Low | A1 | `ActivityLog`, `AdminUsers` | None |
| B11 | Page Sections editor: sections and their items for any page (required before Stage C, because moved text is not editable until then) | Medium | A1, A3 | `PageSections`, `PageSectionItems` | All section headings, introductions, buttons and repeating blocks |

## 5. Stage C: Public pages read from the database

The public site changes in this stage, so each step keeps the fixed text as a fallback until the database content is confirmed.

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| C1 | Site-wide settings: business name, tagline, contact details, social links, copyright, `ShowAddress` | Low | Phase 4 | `WebsiteSettings` | Header, footer, all pages |
| C2 | Home page, seeded with today's wording | Medium | B1, B4, B7, B9, B11 | `HomePageContent`, `PageSections`, `PageSectionItems`, `Services`, `PortfolioProjects`, `BlogPosts`, `Testimonials` | Home page |
| C3 | About page, seeded with today's wording | Low | B2, B5, B11 | `AboutContent`, `PageSections`, `PageSectionItems`, `AboutValues`, `AboutQualifications` | About page |
| C4 | Services page and the six service detail pages | Medium | B1, B11 | `Services`, `PageSections`, `PageSectionItems` | `services.php`, `services/*.php` |
| C5 | Legal pages | Low | B3 | `SitePages` | Privacy, Terms, Copyright |
| C6 | Portfolio overview, category, single project, collaborators | Medium | B6, B7 | `PortfolioProjects`, `ProjectImages`, `Collaborators`, `Testimonials` | `portfolio.php`, `portfolio/*.php` |
| C7 | Blog listing, single post, category, archive, search | Medium | B8, B9 | `BlogPosts`, `BlogCategories`, `AdminUsers` | `blog.php`, `blog/*.php` |
| C8 | Speaking page | Low | B5 | `SpeakingEngagements`, `AboutContent` | `speaking.php`, `services/speaking-engagements.php` |

## 6. Stage D: Enquiries, email and newsletter

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| D1 | PHPMailer and email templates | Medium | Composer, Xneelo mail details | `WebsiteSettings` | All automated emails |
| D2 | Contact form with validation and spam control; notification email; newsletter tick-box rule | High | D1, D4 logic | `ContactMessages`, `Subscribers` | `contact.php`, Enquire buttons |
| D3 | Contact Messages inbox: status, assignment, notes | Medium | A1, D2 | `ContactMessages`, `AdminUsers` | None |
| D4 | Newsletter sign-up, email confirmation, unsubscribe link (POPIA) | High | D1 | `Subscribers` | Home page form (replaces today's "opening soon" message) and new confirm and unsubscribe pages |
| D5 | Subscribers screen: list, import, CSV export, erase on request | Medium | A1, D4 | `Subscribers`, `CampaignRecipients` | None |
| D6 | Newsletter Campaigns: draft, preview, schedule, resumable sending, delivery results | High | D1, D5, cron | `NewsletterCampaigns`, `CampaignRecipients`, `Subscribers` | Newsletter emails |
| D7 | Forgot-password screens | Low | D1 | `PasswordResets`, `AdminUsers` | Link on `login.php` |

## 7. Stage E: Photography store

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| E1 | Products and print options admin; private download files | Medium | A1, A3, Vanessa's product decision | `PhotographyProducts`, `PhotographyProductOptions` | Store content |
| E2 | Gallery and product pages | Medium | E1 | `PhotographyProducts`, `PhotographyProductOptions` | `photography.php`, `photography/gallery.php`, `product.php`, `portfolio/photography-gallery.php` |
| E3 | Cart | Medium | E2 | Session; reads products and options | `photography/cart.php` |
| E4 | Checkout: customer details, shipping, prices recalculated on the server | High | E3, delivery terms | `PhotographyOrders`, `PhotographyOrderItems` | `photography/checkout.php` |
| E5 | Store terms: returns, delivery, VAT display | Low | B3, Vanessa's details | `SitePages`, `WebsiteSettings` | Terms, checkout |
| E6 | PayFast: signed payment request, sandbox first | High | E4, merchant account | `PhotographyOrders`, `PaymentTransactions` | Redirect to PayFast |
| E7 | PayFast notification handler: signature, source, amount and duplicate checks | High | E6 | `PaymentTransactions`, `PhotographyOrders` | Marks orders paid |
| E8 | Payment result pages and order emails | Medium | D1, E7 | `PhotographyOrders` | `payment-success.php`, `payment-failed.php` |
| E9 | Secure digital downloads, if offered | High | E7, E8 | `PhotographyOrders`, `PhotographyOrderItems`, `PhotographyProducts` | Expiring download link |
| E10 | Orders screen: items, payments, delivery status, CSV export | Medium | A1, E7 | `PhotographyOrders`, `PhotographyOrderItems`, `PaymentTransactions` | Customer delivery updates |

## 8. Stage F: Launch readiness

| # | Module | Complexity | Depends on | Database tables | Public-facing effect |
|---|---|---|---|---|---|
| F1 | Google Analytics and cookie notice | Low | C1 | `WebsiteSettings` | Site-wide notice |
| F2 | Real dashboard: statistics, drafts, recent messages and orders | Low | D3, E10, F1 | Reads across modules | None |
| F3 | Data retention clean-up jobs | Medium | Retention decision, cron | `LoginAttempts`, `ActivityLog`, `PasswordResets`, `ContactMessages` | None |
| F4 | SEO: per-page metadata, sitemap, robots file, clean URLs for slugs | Medium | Stage C | All tables with slugs and metadata | Search results, web addresses |
| F5 | Brand assets: official logo, licensed fonts, real photography | Low | Vanessa | None | Site-wide appearance |
| F6 | Deployment to Xneelo: private `.env`, uploads excluded, backups, HTTPS | Medium | Xneelo details | All | Goes live |
| F7 | Final security, accessibility and browser review | Medium | Everything above | None | Site-wide |

## 9. Recommended Phase 5 scope

The list above is too much for one phase to review well. I recommend splitting it:

| Phase | Stages | Why |
|---|---|---|
| **5** | A and B | Needs no outside decisions except GD. Vanessa gets a complete content system to start loading into |
| **6** | C | Connects the public site once content exists |
| **7** | D | Needs mail details |
| **8** | E | Needs Vanessa's product decision and a PayFast account |
| **9** | F | Launch |

If contact enquiries matter more than the store for an early launch, Stage D can come before C with no technical problem, except that D2 and D4 should still be built together.
