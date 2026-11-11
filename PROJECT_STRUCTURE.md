# Project Structure

**Vanessa Talbot Interior Designs**: *"The Balance of Art, Science and Nature in Spaces"*

Stack: HTML5, CSS3, JavaScript, PHP 8, MariaDB, PHPMailer (installed with Composer). PayFast is planned. Hosted on Xneelo Standard Hosting.

> **Status: Phase 10.** Every page in the public menus reads its wording from the CMS. Images are uploaded by drag and drop from the page being edited, kept in the Media Library, and optimised automatically. The contact form, newsletter sign-up and forgot-password emails work through PHPMailer. The SuperAdmin manages users from the admin area. Blog, photography store, newsletter sending and payments are not built yet.

## Folder layout

```
/
├── index.php                  Home
├── about.php
├── services.php               Services overview
├── portfolio.php              Portfolio landing page
├── contact.php                Contact details, location, FAQ, enquiry form (saves and emails)
├── newsletter.php             Newsletter sign-up, and the confirm and unsubscribe links
├── privacy-policy.php         ┐
├── terms-and-conditions.php   ├ Legal pages, wording from SitePages
├── copyright.php              ┘
├── login.php                  Admin sign-in
├── forgot-password.php        Emails a password reset link
├── reset-password.php         Choose a new password from the link
├── logout.php                 Sign-out (POST with CSRF token only)
├── speaking.php               Redirects to services/speaking-engagements.php
├── photography.php            Redirects to the photography gallery until the store exists
├── blog.php                   Placeholder, not linked (blog not built)
│
├── services/                  One file per service; all use includes/page-service.php
│   ├── biophilic-design.php
│   ├── colour-psychology.php       Colour & Design Psychology (web address unchanged)
│   ├── photography-services.php
│   ├── workshops.php
│   ├── speaking-engagements.php
│   └── consultations.php
│
├── portfolio/
│   ├── biophilic-interior-design.php   ┐
│   ├── colour-psychology.php           ├ Use includes/page-portfolio-category.php
│   ├── photography-gallery.php         ┘
│   ├── collaborators.php
│   └── project.php            Single project, found by ?slug=
│
├── blog/                      Placeholders, not linked (blog not built)
├── photography/               Placeholders, not linked (store not built)
│
├── admin/                     Back office; every page calls admin_guard()
│   ├── dashboard.php
│   ├── pages.php                        Pages: every public page, its blocks, and where each is edited
│   ├── settings.php                     Website Settings
│   ├── homepage-content.php             Single-row sections: view and edit
│   ├── about-content.php
│   ├── about-values.php, about-value-edit.php
│   ├── qualifications.php, qualification-edit.php
│   ├── page-sections.php                All sections, grouped by page
│   ├── section-edit.php, item-edit.php  One section and its repeating items
│   ├── services.php, service-edit.php
│   ├── speaking-engagements.php, speaking-edit.php
│   ├── testimonials.php, testimonial-edit.php
│   ├── site-pages.php, site-page-edit.php           Legal Pages
│   ├── portfolio-projects.php, project-edit.php     Projects with gallery
│   ├── project-create.php                           Redirects to project-edit.php
│   ├── project-image-edit.php                       One gallery image
│   ├── collaborators.php, collaborator-edit.php
│   ├── media-library.php, media-edit.php            Media Library
│   ├── media-upload.php, media-list.php             JSON endpoints for drop zones and the picker
│   ├── enquiries.php, enquiry.php                   Contact Messages: list by status, one message
│   ├── subscribers.php                              Newsletter subscribers
│   ├── admin-users.php, user-edit.php               Users: list, add, role, disable, password links
│   └── (placeholders) blogs.php, blog-create.php, blog-edit.php, photography-products.php,
│       photography-orders.php
│
├── includes/                  Never served directly (blocked by .htaccess)
│   ├── config.php             Reads .env; constants, error handling, paths
│   ├── functions.php          e(), url(), asset(), image_url(), is_active(), brand_logo(), link_is_safe()
│   ├── icons.php              icon() inline SVG icons, botanical() decoration
│   ├── database.php           Central PDO connection and query helpers
│   ├── auth.php               Sessions, sign-in, CSRF, activity log, admin_guard(), password resets
│   ├── permissions.php        Role and permission map
│   ├── cms.php                CMS framework: view, form, validation, save, audit
│   ├── cms-modules.php        Field definitions for every editable module
│   ├── cms-records.php        List, create, edit and delete for multi-record modules
│   ├── media.php              Media Library: validated uploads, search, usage checks, deletion
│   ├── media-optimise.php     GD resizing, compression, orientation and thumbnails
│   ├── content.php            Public page content from the database, with rendering helpers
│   ├── page-map.php           Which admin screen edits each block of each page; Edit buttons for staff
│   ├── content-fallback.php   Saved copy of the wording, used only if the database is unreachable
│   ├── page-service.php       Layout for individual service pages
│   ├── page-portfolio-category.php  Layout for portfolio categories
│   ├── page-legal.php         Layout for legal pages
│   ├── header.php             <head>, fonts, CSS; opens the public or admin layout
│   ├── navbar.php             Public main navigation
│   ├── footer.php             Public footer / closes the admin layout
│   ├── topbar.php             Admin top bar
│   ├── sidebar.php            Admin sidebar, filtered by role
│   ├── site-data.php          No longer used by any page; kept as the original seed source
│   ├── mail.php               Every email: mail_send(), templates, branded layout, SMTP or log
│   ├── mail-templates/        One file per email: contact-notification, newsletter-confirm,
│   │                          password-reset, password-changed, account-invite
│   ├── users.php              User management rules: create, update, disable, password links
│   ├── enquiries.php          Contact form: validation, saving, notification
│   ├── newsletter.php         Newsletter: subscribe, confirm, unsubscribe
│   └── payfast.php            PayFast (placeholder)
│
├── assets/
│   ├── css/style.css          Brand tokens (CSS variables), public layout and components
│   ├── css/admin.css          Admin layout, forms, Media Library and drop zones
│   ├── css/responsive.css     Breakpoints at 1100px (menu), 1024px, 768px and 600px
│   ├── js/main.js             Mobile menu; contact form checks; newsletter sign-up in the background
│   ├── js/admin.js            Admin sidebar, delete confirmation, drag and drop, library picker
│   └── images/                Official logo files go here when supplied
│
├── uploads/                   Created on first upload; ignored by Git; must survive deployments
│   ├── media/YYYY/MM/         Public images: name.jpg (main), name-web.jpg, name-thumb.jpg
│   └── originals/YYYY/MM/     Untouched uploads; blocked from the web
│
├── database/                  Never served directly (blocked by .htaccess)
│   ├── schema.sql             29 core tables; safe to re-run
│   ├── migrations.sql         Migrations 002 to 012: page sections, edit stamps, seed content, image details
│   ├── setup.php              Command-line install; safe to re-run; creates the first SuperAdmin
│   ├── optimise-media.php     Command-line: optimise images uploaded before Phase 8; safe to re-run
│   └── CMS_ARCHITECTURE.md    Database design, admin screens, permissions
│
├── vendor/                    PHPMailer, installed by Composer; ignored by Git; must be deployed
├── composer.json, composer.lock  Library list and exact versions (blocked from the web)
├── .env.example               Configuration template (.env itself is private)
└── .htaccess                  Hides private folders and files; blocks scripts in uploads/
```

## How a public page is built

```php
require_once __DIR__ . '/includes/config.php';    // '/../includes/config.php' in subfolders
require_once INCLUDES_PATH . '/content.php';

$sections = site_sections('contact');               // Published PageSections and their items
$pageTitle = $sections['page-header']['Heading'] ?? '';

require_once INCLUDES_PATH . '/header.php';         // includes navbar.php
// <main id="main-content"> ... render with e(), content_paragraphs(), content_link() ...
require_once INCLUDES_PATH . '/footer.php';
```

- `content.php` returns only Published records. If the database fails, it falls back to `content-fallback.php` and logs the problem.
- All text is plain and escaped on output. Links pass through `link_is_safe()`.
- Images are output with `image_url($path, 'full' | 'web' | 'thumb')`, which falls back to the saved path when a size doesn't exist.
- Each block calls `edit_block('page', 'block')`. Signed-in staff see Edit buttons there that open the right admin screen; visitors get nothing. Add new blocks to `page_map()` in `page-map.php`.
- Interface wording stays in the code: menus, form labels, "Learn more", "Enquire", system messages and the copyright line.

## How an admin page is built

Most admin pages are a few lines, because the framework does the work:

```php
require_once __DIR__ . '/../includes/config.php';
require_once INCLUDES_PATH . '/cms.php';

cms_record_list_page('testimonials');     // or cms_record_edit_page(), or cms_singleton_page()
```

- **Field definitions** live in `cms-modules.php`. Each definition gives the table, fields, field types, list columns and permissions. Adding an editable module is mainly a new definition.
- **Every page** starts with `admin_guard('module.action')`, which checks sign-in, then permission, then CSRF on POST.
- **Saving** records `LastEditedBy` and `LastEditedDate`, and writes the changed field names to `ActivityLog`.

## Database setup and changes

- `php database/setup.php` applies `schema.sql`, then `migrations.sql`, loads first-run settings from `.env`, and creates the first SuperAdmin if none exists.
- **Structure changes** go in `migrations.sql` as additive statements, recorded in `SchemaMigrations`.
- **Content seeds** run once only, so re-running setup never restores text that was edited or removed.

## Brand tokens (`assets/css/style.css`)

| Variable | Value | Role |
|---|---|---|
| `--color-forest-green` | `#243A39` | Primary: navigation, footer, buttons, headings |
| `--color-golden-yellow` | `#CDA02D` | Accent: rules, highlights |
| `--color-soft-peach` | `#F6E2D4` | Main section background |
| `--color-earthy-gold` | `#9D7F3C` | Decorative accent: borders, frames, icons |
| `--color-gold-text` | `#7A6230` | Earthy gold darkened for text and links on light backgrounds (WCAG AA) |

## Typography

| Purpose | Brand font | Variable / selector | Web stand-in |
|---|---|---|---|
| Logo, page titles (`h1`), hero | John Mayer (elegant serif) | `--font-display`, `.display-title` | Gilda Display |
| Quotes & taglines | Bayshore (signature script) | `--font-script`, `.tagline` | Allison |
| Main section headings | Helvetica Bold | `--font-heading`, `h2` | Arial (where Helvetica is missing) |
| Labels, nav, buttons | Helvetica, spaced capitals (as "INTERIOR DESIGNS" in the logo) | `.eyebrow`, `.btn`, nav links | Arial |
| Subheadings | Open Sans SemiBold | `--font-subheading`, `h3`–`h6` | n/a |
| Body text, forms | Open Sans | `--font-body` | n/a |

John Mayer and Bayshore need web-licensed `.woff2` files. Put them in `assets/fonts/` as `john-mayer.woff2` and `bayshore.woff2`, then uncomment the `@font-face` block in `style.css`.

## Logo

`brand_logo()` in `functions.php` uses `assets/images/logo.svg` (header) and `assets/images/logo-white.svg` (footer) automatically when those files exist. Until then it renders a text lockup in the same layout as the official logo.

## Colour usage

- **White and Soft Peach** are the main page backgrounds, alternating between sections.
- **Forest Green** is reserved for navigation, the footer, buttons, highlights and dividers.
- **Golden Yellow and Earthy Brown-Gold** are accents: rules, icons, tags, labels and decorative frames.

## Notes

- `BASE_URL` (in `.env`) is empty when the site runs at the domain root. Set it to `/subfolder` when testing locally from a subdirectory.
- Real credentials never go in the repository. They belong in `.env`, which on the live server sits in the folder above the website.
- `APP_ENV` defaults to production, so a missing `.env` never shows error details to visitors.
- `APP_URL` (in `.env`) is the site's public address. Links in emails are built from it.
- Run `composer install --no-dev` after cloning to create `vendor/`. Without it the site works, but no email is sent and the error log says why.
