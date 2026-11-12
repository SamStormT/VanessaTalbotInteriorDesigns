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
| CMS editing | Ready | Every piece of public wording is editable; see `CONTENT_MAPPING.md`. Staff see Edit buttons on the website, and Website Content → Pages lists where every block is edited |
| Media Library | Ready | Drag and drop on every image field, library search and reuse, descriptions and captions |
| Image optimisation | Ready | Every upload is resized, compressed and given a thumbnail; the original is kept privately |
| Testimonials | Admin only | Can be managed, but no public page shows them yet |
| Newsletter sign-up | Ready | Saved to Subscribers, confirmed by email, with unsubscribe |
| Newsletter sending | **Not built** | Sign-ups are collected; nothing is sent to subscribers yet |
| Email delivery | **Partly verified** | Login to Xneelo's mail server succeeds with the `.env` settings. Delivery to real inboxes, and the DNS records that keep mail out of spam, still to check |
| Project documents | Protected | `.htaccess` returns 404 for every `.md` file, tested on Apache |
| Blog | Ready | Articles, categories, search, featured post, draft preview; in the main menu. Shows "first articles are on their way" until posts are published |
| Photography store | Not built | Removed from the menus |
| Production hosting | **Not tested** | Only tested locally |

## 2. Launch blockers

| # | Blocker | What's needed | Owner |
|---|---|---|---|
| 1 | Legal pages are unreviewed drafts | Review by a suitably qualified person; confirm Vanessa as Information Officer | Vanessa |
| 2 | Email settings on the live server, and spam protection | Mailbox and SMTP settings work locally. On Xneelo: `.env` with `APP_URL` set to the https address, add a DMARC record and confirm DKIM is on (SPF exists), then send a test of each email | Samantha |
| 3 | Where enquiries are sent | Fill in "Send contact form messages to" in Website Settings | Vanessa |
| 4 | Privacy Policy doesn't yet describe the contact form and newsletter | Add what is collected (name, email, phone, message, IP address), why, how long it's kept, and how to unsubscribe or ask for deletion | Vanessa |
| 5 | All wording is draft | Vanessa's review of section 3 below | Vanessa |
| 6 | The Photography service mentions an online store that doesn't exist yet | Reword the description and "What's included" under Services | Vanessa |
| 7 | Newsletter sign-ups are collected, but no newsletter is sent yet | Accept for launch (sign-ups wait for the campaign phase), or unpublish `home/newsletter` | Vanessa |
| 8 | No photographs yet | Upload through the admin area | Vanessa |
| 9 | No portfolio projects | At least a few real projects with client permission | Vanessa |
| 10 | Stand-in logo text and fonts | Official logo files; web-licensed John Mayer and Bayshore | Vanessa |
| 11 | Vanessa's phone and street address are in the public GitHub history | Make the repo private, or rewrite history | Samantha |
| 12 | Which email is public: the business card and business plan use a different address from Website Settings | Confirm and update Website Settings | Vanessa |

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

- [ ] Confirm Xneelo's MariaDB is 10.2.1 or newer, and PHP is 8.x with the GD and fileinfo extensions
- [ ] Confirm whether command-line access is available
- [ ] Upload `vendor/` (or run `composer install --no-dev` on the server); without it no email is sent
- [ ] In `.env`: `APP_URL`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_MAILER=smtp`
- [ ] Send a test contact message, newsletter sign-up and password reset, and check they arrive (and not in spam)
- [ ] Confirm Xneelo's hourly sending limit before the newsletter phase
- [ ] Domain registered and pointing at Xneelo; HTTPS certificate active
- [ ] Add an HTTP-to-HTTPS redirect (sign-in needs HTTPS in production)
- [ ] Create the production database and a dedicated database user
- [ ] Put `.env` in the folder above the website with `APP_ENV=production`
- [ ] Run `database/setup.php` on the server (table names must not come from a Windows export)
- [ ] Create the real SuperAdmin with `setup.php`, then add the other people from the Users screen; no test accounts on the server
- [ ] Make a second SuperAdmin (or keep developer access) so the site can never be left without one who can manage users
- [ ] Confirm `.htaccess` is honoured: `/.env`, `/database/`, `/includes/`, `/vendor/`, `/composer.json` and `/README.md` must return 404, a script placed in `uploads/` must not run, and nothing in `uploads/originals/` may be downloadable
- [ ] Make sure PHP can write to `uploads/`, and exclude it from anything that mirrors files
- [ ] Confirm Xneelo's PHP upload limits allow 10 MB files, and that PHP may use 512 MB of memory while processing a large photo
- [ ] Plan disk space: each photo is stored as the original plus three sizes (about 1.3 MB extra for a typical phone photo)
- [ ] Schedule database and `uploads/` backups, and test a restore
- [ ] Add a monitoring check, and review `logs/php-error.log` after launch

## 5. Before going public

- [ ] Confirm the tagline style: "and" or "&", capitalised or not
- [ ] Decide whether the street address is ever shown
- [ ] Decide how long to keep login attempts, activity logs, contact messages and IP addresses (POPIA)
- [ ] Favicon, sharing image, sitemap and robots file
- [ ] Cookie notice, if analytics is added, and update the Privacy Policy to match
- [ ] Real-device testing on phones, plus Safari and Firefox, including a real drag and drop upload
- [ ] Screen reader and keyboard review
- [ ] Performance check once real photographs are in

## 6. Known limitations

- **Database values:** `PortfolioCategory` and `EnquiryType` still store `Colour Psychology` internally. Admin and public forms display Colour & Design Psychology, and the web address stays `colour-psychology`.
- **Menus:** Photography points to the portfolio gallery and Speaking to its service page. These need revisiting when the store is built.
- **Blog formatting:** articles are plain text with headings and bullet points. Links and pictures inside an article are not supported yet; each post has one featured image.
- **Sections:** new page sections are added by a developer. On service pages a new section appears automatically. On other pages, the order is set by the design.
- **Editors and live content:** an Editor changing an already Published record changes the site immediately. Only new records wait for an Admin.
- **Images:** HEIC photos from iPhones are not accepted, and photos over about 50 megapixels are refused. Speaking Engagement and Testimonial images are stored but not yet shown publicly. Images uploaded before optimisation stay full size until `php database/optimise-media.php` is run.
- **Fallback text** is a saved copy of the seeded wording. Phone and email are not in it, so they don't show if the database is down. The contact form and newsletter then show a "please try again later" message.
- **Email** depends on the mail server. If it's unreachable, messages and sign-ups are still saved and the failure is logged, but nobody is notified, so check Contact Messages in the admin area regularly.
- **Contact Messages** has no reply-from-admin feature: replies go from Vanessa's own mailbox, using the Reply button.
- **Activity log:** there is no full log screen yet; the dashboard shows the latest eight entries to the SuperAdmin.
- **Users:** accounts are disabled, never deleted, so names stay on past edits and in the log. There is no "My account" page yet: people change their own password with "Forgot your password?", and a SuperAdmin changes names, emails and roles.

## 7. Recommended route to launch

| Step | Scope | Needs |
|---|---|---|
| 1 | Make the repo private | Nothing further |
| 2 | Vanessa uploads photos and replaces wording | Her content |
| 3 | **First public launch** as a brochure site, with the contact form and newsletter sign-up working | Confirmed wording, legal review, hosting checklist, website mailbox |
| 4 | Newsletter campaigns | Approval, Xneelo sending limits |
| 5 | Blog | Done |
| 6 | Photography store and PayFast | Approval, product decision, merchant account |
