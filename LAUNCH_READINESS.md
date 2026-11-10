# Launch Readiness

**Verdict: not ready for public launch, but close to a brochure-site launch.** Every page in the menus is built and editable, and Vanessa can upload her own images. What remains:

- Real content and photographs
- Legal review
- A decision on enquiries
- Production hosting

## 1. Status by area

| Area | Status | Notes |
|---|---|---|
| Home, About, Services, six service pages, footer | Built, pending wording | Read from the CMS, with fallback text if the database is down |
| Portfolio landing, three categories, single project, Collaborators | Built, no real work yet | Show a "coming soon" state until projects are published |
| Contact page: details, location, FAQ | Built, pending wording | Phone and email come from Website Settings |
| Contact form | **Interface only** | Sends and stores nothing, and says so on the page |
| Privacy Policy, Terms, Copyright | Built, **needs legal review** | Draft wording, editable under Legal Pages |
| Admin sign-in, roles, activity log | Ready | Tested on the local stack and Apache |
| CMS editing | Ready | Every piece of public wording is editable; see `CONTENT_MAPPING.md` |
| Media Library | Ready | Drag and drop on every image field, library search and reuse, descriptions and captions |
| Image resizing | **Not built** | GD is installed, but uploads are stored at full size |
| Testimonials | Admin only | Can be managed, but no public page shows them yet |
| Newsletter form | **Not connected** | Says sign-ups are opening soon; saves nothing |
| Blog and photography store | Not built | Removed from the menus |
| Production hosting | **Not tested** | Only tested locally |

## 2. Launch blockers

| # | Blocker | What's needed | Owner |
|---|---|---|---|
| 1 | Legal pages are unreviewed drafts | Review by a suitably qualified person; confirm Vanessa as Information Officer | Vanessa |
| 2 | Enquiries depend on phone and email | Accept that for launch, or approve contact form processing | Samantha |
| 3 | All wording is draft | Vanessa's review of section 3 below | Vanessa |
| 4 | The Photography service mentions an online store that doesn't exist yet | Reword the description and "What's included" under Services | Vanessa |
| 5 | Newsletter section invites sign-ups that are not saved | Unpublish `home/newsletter` in Page Sections, or approve the feature | Samantha |
| 6 | No photographs yet | Upload through the admin area | Vanessa |
| 7 | Large photos would slow pages, because nothing resizes them | Add automatic resizing (GD is ready), or upload web-sized images only | Development |
| 8 | No portfolio projects | At least a few real projects with client permission | Vanessa |
| 9 | Stand-in logo text and fonts | Official logo files; web-licensed John Mayer and Bayshore | Vanessa |
| 10 | No "forgot password" | Requires the email phase; until then a developer resets it | Development |
| 11 | No Users screen | Accounts can only be created with `setup.php` or SQL; at least one Admin besides the SuperAdmin should be set up before handover | Development |
| 12 | Vanessa's phone and street address are in the public GitHub history | Make the repo private, or rewrite history | Samantha |
| 13 | Which email is public: the business card and business plan use a different address from Website Settings | Confirm and update Website Settings | Vanessa |

Blog and store placeholder pages still exist at their old addresses, but nothing links to them.

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
- [ ] Domain registered and pointing at Xneelo; HTTPS certificate active
- [ ] Add an HTTP-to-HTTPS redirect (sign-in needs HTTPS in production)
- [ ] Create the production database and a dedicated database user
- [ ] Put `.env` in the folder above the website with `APP_ENV=production`
- [ ] Run `database/setup.php` on the server (table names must not come from a Windows export)
- [ ] Create the real SuperAdmin with a strong password; no test accounts on the server
- [ ] Confirm `.htaccess` is honoured: `/.env`, `/database/` and `/includes/` must return 404, and a script placed in `uploads/` must not run
- [ ] Make sure PHP can write to `uploads/`, and exclude it from anything that mirrors files
- [ ] Confirm Xneelo's PHP upload limits allow 10 MB files
- [ ] Schedule database and `uploads/` backups, and test a restore
- [ ] Add a monitoring check, and review `logs/php-error.log` after launch

## 5. Before going public

- [ ] Confirm the tagline style: "and" or "&", capitalised or not
- [ ] Decide whether the street address is ever shown
- [ ] Decide how long to keep login attempts, activity logs and IP addresses (POPIA)
- [ ] Favicon, sharing image, sitemap and robots file
- [ ] Cookie notice, if analytics is added, and update the Privacy Policy to match
- [ ] Real-device testing on phones, plus Safari and Firefox, including a real drag and drop upload
- [ ] Screen reader and keyboard review
- [ ] Performance check once real photographs are in

## 6. Known limitations

- **Database values:** `PortfolioCategory` and `EnquiryType` still store `Colour Psychology` internally. Admin and public forms display Colour & Design Psychology, and the web address stays `colour-psychology`.
- **Menus:** Photography points to the portfolio gallery, Speaking to its service page, and Blog is removed. These need revisiting when those features are built.
- **Sections:** new page sections are added by a developer. On service pages a new section appears automatically. On other pages, the order is set by the design.
- **Editors and live content:** an Editor changing an already Published record changes the site immediately. Only new records wait for an Admin.
- **Images:** HEIC photos from iPhones are not accepted, and images are not resized. Speaking Engagement and Testimonial images are stored but not yet shown publicly.
- **Fallback text** is a saved copy of the seeded wording. Phone and email are not in it, so they don't show if the database is down.
- **Activity log:** there is no full log screen yet; the dashboard shows the latest eight entries to the SuperAdmin.

## 7. Recommended route to launch

| Step | Scope | Needs |
|---|---|---|
| 1 | Make the repo private. Add image resizing | Approval |
| 2 | Vanessa uploads photos and replaces wording | Her content |
| 3 | **First public launch** as a brochure site, with enquiries by phone and email and the newsletter section unpublished | Confirmed wording, legal review, hosting checklist |
| 4 | Users screen, email, "forgot password", contact form and newsletter | Approval, mail details |
| 5 | Blog | Approval |
| 6 | Photography store and PayFast | Approval, product decision, merchant account |
