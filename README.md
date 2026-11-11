# Vanessa Talbot Interior Designs

> The Balance of Art, Science and Nature in Spaces.

## Overview

Vanessa Talbot Interior Designs is a bespoke website and content management system (CMS) for a practice focused on Biophilic Interior Design, Colour & Design Psychology, Photography, Workshops, Speaking Engagements and Consultations.

The website reflects Vanessa Talbot's background in science and interior design: spaces that promote harmony, wellbeing and a deeper connection with nature.

**Current status:** a complete brochure website, managed through the CMS, with a working contact form, newsletter sign-up and password reset by email. The SuperAdmin manages staff accounts from the admin area. Newsletter sending, the blog and the photography store are planned but not yet built. See [LAUNCH_READINESS.md](LAUNCH_READINESS.md) for what remains before launch.

---

## Public website

| Page | Status |
|---|---|
| Home, About, Services | Built, wording managed in the CMS |
| Six service pages: Biophilic Design, Colour & Design Psychology, Photography, Workshops, Speaking Engagements, Consultations | Built |
| Portfolio: landing page, Biophilic Interior Design, Colour & Design Psychology, Photography, Collaborators, single project | Built; shows "coming soon" until projects are published |
| Contact: details, location, frequently asked questions, enquiry form | Built; messages are saved and emailed to the business |
| Newsletter sign-up (home page, and a tick-box on the contact form) | Built; confirmed by email, with an unsubscribe link |
| Privacy Policy, Terms and Conditions, Copyright Notice | Built, draft wording |
| Blog | Planned. Not linked from the menus |
| Photography store, cart, checkout and PayFast | Planned. Not linked from the menus |

If the database is unreachable, public pages show a saved copy of the wording instead of an error.

---

## Content management (admin area)

Signed-in staff can edit every piece of website wording without a developer.

| Area | What can be managed |
|---|---|
| Website Settings | Business name, tagline, phone, email, location, whether the street address is shown, social links, footer text |
| Homepage Content, About Content | Hero, introduction, quote, story, speaking topics, search engine details |
| Page Sections | Headings, introductions, buttons and repeating blocks on every page, including FAQs and "how it works" steps |
| Services | Each service's wording, inclusions, client types, image and visibility |
| About Values, Qualifications | Individual values; qualifications, certifications, memberships and awards |
| Portfolio Projects | Projects with gallery images; publishing requires client permission |
| Collaborators, Testimonials | Publishing requires a recorded permission date |
| Speaking Engagements | Talks and events |
| Legal Pages | Privacy Policy, Terms, Copyright |
| Media Library | Every uploaded image, with search, preview, descriptions and captions |
| Contact Messages | Every enquiry, filtered by status, with a reply button and private notes |
| Subscribers | Newsletter sign-ups, whether they confirmed, unsubscribe and erase on request |
| Users (SuperAdmin only) | Add people by email invitation, change roles, disable and reactivate accounts, send password reset links |

**Workflow:** content is Draft, Published or Archived, and only Published content appears on the site. Every change records who made it and when, and is written to an activity log.

**Roles:** SuperAdmin (everything), Admin (content, settings, media, and later orders, subscribers and messages), Editor (prepares content; an Admin publishes it).

**Images:** every image field accepts drag and drop or "Choose from library". Uploads go into the Media Library automatically, so nobody types file paths. Galleries and the library accept several images at once. Each upload is turned upright, resized, compressed and given a thumbnail, while the untouched original is kept privately.

**Email:** contact messages are emailed to the address in Website Settings, with Reply-To set to the visitor. Staff who forget their password can reset it from the sign-in page.

Planned admin areas (placeholders today): Blog, Photography Products and Orders, Newsletter Campaigns.

---

## Technology

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (no framework) |
| Backend | PHP 8.x with PDO prepared statements |
| Database | MariaDB 10.2.1 or newer |
| Email | PHPMailer 6, installed with Composer |
| Payments | PayFast (planned) |
| Hosting | Xneelo Standard Hosting |
| Version control | Git and GitHub |

---

## Running it locally

1. Install XAMPP (PHP 8 and MariaDB). In `C:\xampp\mysql\bin\my.ini`, add `lower_case_table_names=2` under `[mysqld]`. Enable `extension=gd` in `php.ini`.
2. Copy `.env.example` to `.env` and fill in the database details. Keep `MAIL_MAILER=log` locally: emails are then written to `logs/mail/` instead of being sent.
3. Run `composer install` to add PHPMailer to `vendor/`.
4. Create an empty database, then run `php database/setup.php`. It creates all tables, adds the starting content, and asks for the first SuperAdmin account. It is safe to run again.
5. Run `php -S localhost:8000` in the project folder and open http://localhost:8000. The admin area is at `/login.php`.

Configuration and secrets live only in `.env`, which Git ignores. On the live server, `.env` belongs in the folder above the website.

---

## Documentation

| Document | Contents |
|---|---|
| [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) | Folders, files, how pages are built, brand tokens |
| [database/CMS_ARCHITECTURE.md](database/CMS_ARCHITECTURE.md) | Database design, relationships, admin screens, permissions |
| [CONTENT_MAPPING.md](CONTENT_MAPPING.md) | Where every piece of website wording is stored |
| [CONTENT_REQUIREMENTS.md](CONTENT_REQUIREMENTS.md) | What Vanessa needs to supply |
| [LAUNCH_READINESS.md](LAUNCH_READINESS.md) | Launch blockers and checklists |

---

## Brand identity

**Business name:** Vanessa Talbot Interior Designs

**Tagline:** The Balance of Art, Science and Nature in Spaces.

| Colour | Hex |
|---|---|
| Forest Green | #243A39 |
| Golden Yellow | #CDA02D |
| Soft Peach | #F6E2D4 |
| Earthy Brown-Gold | #9D7F3C |

**Typography:** John Mayer (logo and page titles), Bayshore (quotes and taglines), Helvetica (headings and labels), Open Sans (body text and forms). Licensed web versions of John Mayer and Bayshore are still to be supplied; free stand-ins are used until then.

---

## Security

- Passwords hashed with PHP's `password_hash()`; sign-in rate limiting
- Secure, HttpOnly session cookies with idle and absolute time-outs
- CSRF protection on every form; spam traps and hourly limits on the public forms
- Password reset links are single-use, expire after an hour, are stored only as hashes, and never reveal whether an account exists
- Role-based permissions checked on the server for every admin page
- The last active SuperAdmin can't be disabled or demoted, and nobody can change their own role
- Prepared statements for all database queries; all output escaped
- Uploads checked by their real content, stored under generated names, and scripts blocked in `uploads/`
- Published images are re-encoded, which removes hidden photo data such as GPS location; originals are not publicly reachable
- `.env`, `database/`, `includes/`, `logs/`, `vendor/`, Composer files and all `.md` documents blocked from the web

---

## Future enhancements

Online consultations, workshop bookings, corporate training, online courses, a client portal, a community membership area, photography licensing, event registration and digital product sales.

---

## License

Copyright © Vanessa Talbot Interior Designs. All Rights Reserved.

The contents of this repository, including source code, branding, logos, images, photography, written content and design assets, may not be copied, reproduced, distributed, modified or reused without prior written permission. See [LICENSE.md](LICENSE.md).
