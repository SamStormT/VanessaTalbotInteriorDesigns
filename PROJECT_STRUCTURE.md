# Project Structure

**Vanessa Talbot Interior Designs**: *"The Balance of Art, Science and Nature in Spaces"*

Stack: HTML5, CSS3, JavaScript, PHP 8, MariaDB, PHPMailer, PayFast. Hosted on Xneelo Standard Hosting.

> **Status: Phase 4.** The public site is built but still uses fixed content. The admin area has working sign-in, permissions, Website Settings, Homepage, About and Services editing; other admin screens are placeholders. Email, payments and public forms are not built yet.

## Folder layout

```
/
├── index.php                  Home
├── about.php
├── services.php               Services overview
├── portfolio.php              Portfolio overview
├── photography.php            Photography overview
├── blog.php                   Blog listing
├── contact.php
├── speaking.php
├── privacy-policy.php
├── terms-and-conditions.php
├── copyright.php
├── login.php                  Admin login
│
├── services/                  Individual service pages
│   ├── biophilic-design.php
│   ├── colour-psychology.php
│   ├── photography-services.php
│   ├── workshops.php
│   ├── speaking-engagements.php
│   └── consultations.php
│
├── portfolio/
│   ├── biophilic-interior-design.php
│   ├── colour-psychology.php
│   ├── photography-gallery.php
│   ├── collaborators.php
│   └── project.php            Single project view
│
├── blog/
│   ├── blog-post.php          Single post
│   ├── category.php
│   ├── archive.php
│   └── search.php
│
├── photography/               Print store (PayFast)
│   ├── gallery.php
│   ├── product.php
│   ├── cart.php
│   ├── checkout.php
│   ├── payment-success.php
│   └── payment-failed.php
│
├── admin/                     Back office (requires login)
│   ├── dashboard.php
│   ├── subscribers.php
│   ├── enquiries.php
│   ├── blogs.php / blog-create.php / blog-edit.php
│   ├── portfolio-projects.php / project-create.php / project-edit.php
│   ├── photography-products.php
│   ├── photography-orders.php
│   ├── speaking-engagements.php
│   ├── testimonials.php
│   ├── media-library.php
│   ├── admin-users.php
│   └── settings.php
│
├── includes/
│   ├── config.php             Constants, environment, paths; loads functions.php
│   ├── functions.php          e(), url(), asset(), is_active()
│   ├── icons.php              icon() inline SVG icons, botanical() decoration
│   ├── site-data.php          Shared content: services, values, target markets, portfolio categories
│   ├── database.php           Central PDO connection and query helpers
│   ├── auth.php               Sessions, sign-in, CSRF, activity log, admin_guard(), password resets
│   ├── permissions.php        Role and permission map
│   ├── cms.php                CMS framework: view, form, validation, save, audit
│   ├── cms-modules.php        Field definitions for each editable module
│   ├── mail.php               PHPMailer (placeholder)
│   ├── payfast.php            PayFast payments + ITN (placeholder)
│   ├── header.php             <head>, fonts, CSS; opens public or admin layout
│   ├── navbar.php             Public main navigation with dropdowns
│   ├── footer.php             Public footer + legal links / closes admin layout
│   ├── topbar.php             Admin top bar
│   └── sidebar.php            Admin sidebar navigation
│
├── assets/
│   ├── css/style.css          Brand tokens (CSS variables), base, header, footer
│   ├── css/admin.css          Admin layout
│   ├── css/responsive.css     Breakpoints at 1024px / 768px
│   ├── js/main.js             Mobile nav toggle
│   ├── js/admin.js            Admin sidebar toggle
│   └── images/
│
└── database/
    ├── schema.sql             All 29 tables; safe to re-run
    ├── migrations.sql         Later changes and seed data; safe to re-run
    ├── setup.php              Command-line install: applies both files, creates first SuperAdmin
    └── CMS_ARCHITECTURE.md    ERD, CRUD plan, permissions, recommendations
```

## How a page is built

Public page:

```php
require_once __DIR__ . '/includes/config.php';   // '/../includes/config.php' in subfolders
$pageTitle = 'About';
require_once INCLUDES_PATH . '/header.php';       // includes navbar.php
// ... <main id="main-content"> ...
require_once INCLUDES_PATH . '/footer.php';
```

Admin page: the same, plus `require_once INCLUDES_PATH . '/auth.php'; require_admin();` and `$layout = 'admin';`. The header then loads `admin.css`, `sidebar.php` and `topbar.php` instead of the public navbar.

## Brand tokens (`assets/css/style.css`)

| Variable | Value | Role |
|---|---|---|
| `--color-forest-green` | `#243A39` | Primary: header, footer, headings |
| `--color-golden-yellow` | `#CDA02D` | Accent: logo, hover, highlights |
| `--color-soft-peach` | `#F6E2D4` | Surface: light sections, text on green |
| `--color-earthy-gold` | `#9D7F3C` | Decorative accent: borders, frames |
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
- **Forest Green** is reserved for the footer, buttons, navigation text, highlights and dividers.
- **Golden Yellow and Earthy Brown-Gold** are accents: rules, icons, tags, labels and decorative frames.

## Notes

- `BASE_URL` in `config.php` is empty when the site runs at the domain root. Set it to `/subfolder` when testing locally from a subdirectory.
- Real credentials must never be committed. A private config outside the web root is planned for a later phase.
- Until authentication is built, every admin page redirects to `login.php`.
