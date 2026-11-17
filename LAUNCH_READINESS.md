# Launch Readiness

**Verdict: not ready for public launch, but close to a brochure-site launch.** Every page in the menus is built and editable, Vanessa can upload her own images, and enquiries, newsletter sign-ups and password resets work by email. What remains:

- Real content and photographs
- Legal review, now including how enquiries and subscribers are handled
- Production hosting and the mailbox the site sends from

## 1. Status by area

| Area | Status | Notes |
|---|---|---|
| Home, About, Services, six service pages, footer | Built, pending wording | Read from the CMS, with fallback text if the database is down |
| Portfolio landing, three categories, single project, Collaborators | Built, no real work yet | Show a "coming soon" state until projects are published |
| Contact page: details, location, FAQ | Built, pending wording | Phone and email come from Website Settings |
| Contact form | Ready | Saved to Contact Messages and emailed to the business; spam trap and hourly limit |
| Privacy Policy, Terms, Copyright | Built, **needs legal review** | Draft wording, editable under Legal Pages |
| Admin sign-in, roles, activity log | Ready | Tested on the local stack and Apache. Forgot password works by email |
| User management | Ready | SuperAdmin adds users by email invitation, changes roles, disables and reactivates, sends reset links |
| CMS editing | Ready | Every piece of public wording is editable; see `CONTENT_MAPPING.md`. Staff see Edit buttons on the website, can click any wording or picture to edit that exact field, and return to the page after saving. Website Content → Pages lists where every block is edited. Every edit is kept in History and can be undone (last 50 per item) |
| Media Library | Ready | Drag and drop on every image field, library search and reuse, descriptions and captions |
| Image optimisation | Ready | Every upload is resized, compressed and given a thumbnail; the original is kept privately |
| Testimonials | Ready | Home, service and project pages, once published with the date of written permission |
| Book a consultation | Ready | `book-consultation.php`; arrives as a Consultation enquiry |
| WhatsApp button | Ready | Shows when a WhatsApp number is set in Website Settings |
| Newsletter sign-up | Ready | Saved to Subscribers, confirmed by email, with unsubscribe |
| Newsletter campaigns | Ready | Built from blog posts or as a round-up of the newest articles; preview, test, send now or schedule; batches with an hourly limit; one-click unsubscribe. Scheduled sending on time needs a scheduled task (cron) on the host |
| Welcome email | Ready, switched off | Sent once when someone confirms. Vanessa reads the wording, sends a test, then switches it on |
| Search and sharing | Ready | Favicon, home-screen icon, sharing picture, page addresses and sharing tags, `sitemap.xml`, `robots.txt`. Needs `APP_URL` on the live site |
| SEO and GEO | Ready | Search wording on every page, Google preview on edit screens, structured data (business, Vanessa, services, articles, breadcrumbs, FAQ), author box, category pages, `/llms.txt`, AI crawler rules, speed settings, Search health screen. Works fully once live |
| Email delivery | **Partly verified** | Login to Xneelo's mail server succeeds with the `.env` settings. Delivery to real inboxes, and the DNS records that keep mail out of spam, still to check |
| Project documents | Protected | `.htaccess` returns 404 for every `.md` file, tested on Apache |
| Blog | Ready | Articles, categories, search, featured post, draft preview, scheduled publishing, share links; in the main menu. Shows "first articles are on their way" until posts are published |
| Photography store | Not built | Removed from the menus |
| Production hosting | **Not tested** | Only tested locally |

## 2. Launch blockers

| # | Blocker | What's needed | Owner |
|---|---|---|---|
| 1 | Legal pages are unreviewed drafts | Review by a suitably qualified person; confirm Vanessa as Information Officer | Vanessa |
| 2 | Email settings on the live server, and spam protection | Mailbox and SMTP settings work locally. On the new host (Xneelo now keeps only the domain; hosting to be decided): `.env` with `APP_URL` set to the https address, add a DMARC record and confirm DKIM is on (SPF exists), then send a test of each email | Samantha |
| 3 | Where enquiries are sent | Fill in "Send contact form messages to" in Website Settings | Vanessa |
| 4 | Privacy Policy doesn't yet describe the contact form and newsletter | Add what is collected (name, email, phone, message, IP address), why, how long it's kept, and how to unsubscribe or ask for deletion | Vanessa |
| 5 | All wording is draft | Vanessa's review of section 3 below | Vanessa |
| 6 | The Photography service mentions an online store that doesn't exist yet | Reword the description and "What's included" under Services | Vanessa |
| 7 | Newsletter logo and sending limit | Supply `assets/images/logo-email.png` (PNG, about 400px wide; email apps don't show SVG), and set `NEWSLETTER_HOURLY_LIMIT` below the email provider's hourly limit | Vanessa, Samantha |
| 8 | No photographs yet | Upload through the admin area | Vanessa |
| 9 | No portfolio projects | At least a few real projects with client permission | Vanessa |
| 10 | Stand-in logo text and fonts | Official logo files; web-licensed John Mayer and Bayshore | Vanessa |
| 11 | Vanessa's phone and street address are in the public GitHub history | Make the repo private, or rewrite history | Samantha |
| 12 | Public email | The address on the business card, but the questionnaire gives two others (vanessa@talbot.co.za and vanessa@vanessatalbotinteriordesigns.co.za). Confirm the current one and enter it in Website Settings → Email (local copy and live site); it is not stored in the code | Vanessa |
| 13 | Second domain vtid.co.za | If registered, set a permanent (301) redirect at the host to the main domain | Samantha |

Store placeholder pages still exist at their old addresses, but nothing links to them. Old blog addresses redirect to the blog page.

## 3. Placeholder content Vanessa must replace

Everything below can be changed in the admin area. Page Sections are listed by page name.

| Where in admin | What to review or replace |
|---|---|
| Homepage Content | Hero wording, hero image, introduction image |
| About Content | Her real story, portrait, speaking topics |
| Page Sections: About | Journey steps, philosophy, "coming soon" notices |
| About Values | The five value descriptions |
| Qualifications | Add real qualifications and memberships (none exist yet) |
| Services | All six: descriptions, "What's included", "Ideal for", images. Remove store wording from Photography |
| Page Sections: service pages | "How it works" steps, specialised services, closing text |
| Portfolio Projects | Add real projects, with client permission and gallery images |
| Collaborators | Add real collaborators, with the date they agreed |
| Page Sections: portfolio pages | Page introductions and "coming soon" text |
| Page Sections: Contact | Introductions, location wording ("Visits are by appointment"), all six FAQ answers, including the budget answer |
| Legal Pages | Privacy Policy, Terms, Copyright |
| Speaking Engagements, Testimonials | Add real entries, with written permission for testimonials |
| Media Library | Add a description to every image marked "Needs description" |
| Website Settings | Confirm phone, email, city; add social links |

## 4. Hosting checklist

- [ ] Confirm the new host's MariaDB (or MySQL 8) is 10.2.1 or newer, and PHP is 8.x with the GD and fileinfo extensions
- [ ] Confirm whether command-line access is available
- [ ] Confirm the host runs Apache (or LiteSpeed) so `.htaccess` works, including the rewrite rules for `/robots.txt` and `/sitemap.xml`
- [ ] Set up a scheduled task (cron) every 10 minutes: `php database/send-campaigns.php` (or `CRON_KEY` and `scheduled-tasks.php`); needed for scheduled newsletters
- [ ] Upload `vendor/` (or run `composer install --no-dev` on the server); without it no email is sent
- [ ] In `.env`: `APP_URL`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_MAILER=smtp`
- [ ] Send a test contact message, newsletter sign-up and password reset, and check they arrive (and not in spam)
- [ ] Confirm the email provider's hourly sending limit
- [ ] Domain (registered with Xneelo) pointing at the new host; HTTPS certificate active
- [ ] Add an HTTP-to-HTTPS redirect (sign-in needs HTTPS in production)
- [ ] Create the production database and a dedicated database user
- [ ] Put `.env` in the folder above the website with `APP_ENV=production`
- [ ] Run `database/setup.php` on the server (table names must not come from a Windows export). On an existing copy, back up the database first; setup then adds migrations 016 to 021 (021 brings in Vanessa's questionnaire answers, changing only wording that hasn't been edited)
- [ ] Create the real SuperAdmin with `setup.php`, then add the other people from the Users screen; no test accounts on the server
- [ ] Make a second SuperAdmin (or keep developer access) so the site can never be left without one who can manage users
- [ ] Confirm `.htaccess` is honoured: `/.env`, `/database/`, `/includes/`, `/vendor/`, `/composer.json` and `/README.md` must return 404, a script placed in `uploads/` must not run, and nothing in `uploads/originals/` may be downloadable
- [ ] Make sure PHP can write to `uploads/`, and exclude it from anything that mirrors files
- [ ] Confirm the host's PHP upload limits allow 10 MB files, and that PHP may use 512 MB of memory while processing a large photo
- [ ] Plan disk space: each photo is stored as the original plus three sizes (about 1.3 MB extra for a typical phone photo)
- [ ] Schedule database and `uploads/` backups, and test a restore
- [ ] Add a monitoring check, and review `logs/php-error.log` after launch

## 5. Before going public

- [x] Tagline style: "The balance of art, science & nature in spaces" (from the branding document)
- [ ] Decide whether the street address is ever shown
- [ ] Confirm how long personal details are kept (Website Settings → Privacy; starting values: sign-ins and IP addresses 90 days, answered messages, activity and unsubscribed people 24 months, statistics 25 months) and say so in the Privacy Policy
- [ ] Change the email (SMTP) password before launch: it was shared in a chat during development. Only `.env` should hold it
- [ ] Everyone with an admin account switches on two-step sign-in (My account); the SuperAdmin first
- [ ] On the host, check the security headers arrive (for example securityheaders.com) and that mod_headers, mod_expires and mod_deflate are on
- [ ] Run `composer audit` before each deployment, and keep PHPMailer up to date
- [ ] Uptime monitoring (a free service that checks the home page every few minutes and emails you)
- [x] Favicon, sharing image, sitemap and robots file (replace the sharing picture and icon when the official logo arrives)
- [ ] Submit `sitemap.xml` in Google Search Console after launch (paste the Search Console code in Website Settings first)
- [ ] Bing Webmaster Tools: add the site (it can import from Search Console); paste its code in Website Settings
- [ ] Google Business Profile: category "Interior designer", areas served, photos, and ask clients for reviews
- [ ] Same business name, phone and email on Instagram, Facebook, LinkedIn, Pinterest, Houzz and directories
- [ ] Open Marketing → Search health and work through the "To improve" items
- [ ] Check the live site with Google's Rich Results Test
- [x] Visitor statistics without cookies (Marketing → Statistics), so no cookie notice is needed. If Google Analytics is ever added, a cookie notice and Privacy Policy update are needed first
- [ ] Privacy Policy: mention the cookie-free statistics, the "we received it" emails, and the keeping periods
- [ ] Real-device testing on phones, plus Safari and Firefox, including a real drag and drop upload
- [ ] Screen reader and keyboard review
- [ ] Performance check once real photographs are in

## 6. Known limitations

- **Database values:** `PortfolioCategory` and `EnquiryType` still store `Colour Psychology` internally. Admin and public forms display Colour & Design Psychology, and the web address stays `colour-psychology`.
- **Menus:** Photography points to the portfolio gallery and Speaking to its service page. These need revisiting when the store is built.
- **Article formatting:** the visual editor covers bold, italic, links, headings, lists, quotes, pictures, coloured sections, buttons and the newsletter "read more" point. Tables, videos, underlining and text colours are not supported. Pasting from Word keeps the words, not the fonts.
- **Newsletters:** sending needs the campaign page open (or `database/send-campaigns.php` on a schedule). There is no scheduled sending, open or click tracking. Pictures in emails load from the live website, so they only appear once it's online.
- **Sections:** new page sections are added by a developer. On service pages a new section appears automatically. On other pages, the order is set by the design.
- **Editors and live content:** an Editor changing an already Published record changes the site immediately. Only new records wait for an Admin.
- **Images:** HEIC photos from iPhones are not accepted, and photos over about 50 megapixels are refused. Speaking Engagement and Testimonial images are stored but not yet shown publicly. Images uploaded before optimisation stay full size until `php database/optimise-media.php` is run.
- **Fallback text** is a saved copy of the seeded wording. Phone and email are not in it, so they don't show if the database is down. The contact form and newsletter then show a "please try again later" message.
- **Email** depends on the mail server. If it's unreachable, messages and sign-ups are still saved and the failure is logged, but nobody is notified, so check Contact Messages in the admin area regularly.
- **Contact Messages** has no reply-from-admin feature: replies go from Vanessa's own mailbox, using the Reply button.
- **Activity log:** System → Activity log (SuperAdmin). The dashboard shows the latest eight entries.
- **Users:** accounts are disabled, never deleted, so names stay on past edits and in the log. People change their own name and password on My account; a SuperAdmin changes email addresses and roles.
- **Two-step sign-in** is optional per person (strongly recommended, and reminded on the dashboard). Someone who loses their phone and their backup codes asks a SuperAdmin to switch it off.
- **Statistics** count page views, not people, and leave out robots and signed-in staff; visits from a typed address, a bookmark, an email or an app don't show a source.
- **Error pages:** `.htaccess` assumes the website is at the root of the domain for its 404 and 500 pages; with BASE_URL set, put that folder in front.

## 7. Recommended route to launch

| Step | Scope | Needs |
|---|---|---|
| 1 | Make the repo private | Nothing further |
| 2 | Vanessa uploads photos and replaces wording | Her content |
| 3 | **First public launch** as a brochure site, with the contact form and newsletter sign-up working | Confirmed wording, legal review, hosting checklist, website mailbox |
| 4 | Newsletter campaigns | Done |
| 5 | Blog | Done |
| 6 | Photography store and PayFast | Approval, product decision, merchant account, and photo protection (watermark, web-size copies only, no right-click saving) |

**Later, with the photography store:** Photo protection (later, with the photography store): show a visible watermark on gallery and store photos, publish only reduced web-size copies (never the print files), and switch off right-click saving on those images. No website can fully stop screenshots, so the watermark and the low resolution are what actually protect the work; originals are only ever sent to paying customers.
