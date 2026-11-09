# Launch Readiness

**Verdict: not ready for public launch, but close to a brochure-site launch.** Every page in the menus is now built and editable. What remains is real content, legal review, working enquiries or a decision to rely on phone and email, image uploads, and production hosting.

## 1. Status by area

| Area | Status | Notes |
|---|---|---|
| Home, About, Services, six service pages, footer | Built, pending wording | Read from the CMS, with fallback text if the database is down |
| Portfolio landing, three categories, single project, Collaborators | Built, no real work yet | Show a "coming soon" state until projects are published |
| Contact page, details, FAQ, location | Built, pending wording | Phone and email come from Website Settings |
| Contact form | **Interface only** | Sends and stores nothing, and says so on the page |
| Privacy Policy, Terms, Copyright | Built, **needs legal review** | Draft wording, editable under Legal Pages |
| Admin sign-in, roles, activity log | Ready | Tested on the local stack and Apache |
| Images | **No uploads** | Paths are typed by hand; files must be placed on the server |
| Newsletter form | **Not connected** | Says sign-ups are opening soon; saves nothing |
| Blog and photography store | Not built | Removed from the menus; awaiting approval |
| Production hosting | **Not tested** | Only tested locally |

## 2. Launch blockers

| # | Blocker | What's needed | Owner |
|---|---|---|---|
| 1 | Legal pages are unreviewed drafts | Review by a suitably qualified person; confirm Vanessa as Information Officer | Vanessa |
| 2 | Enquiries depend on phone and email | Accept that for launch, or approve contact form processing | Samantha |
| 3 | All wording is draft | Vanessa's review of section 3 below | Vanessa |
| 4 | Photography service mentions an online store that doesn't exist yet | Reword the description and "What's included" under Services | Vanessa |
| 5 | Newsletter section invites sign-ups that are not saved | Unpublish `home/newsletter`, or approve the feature | Samantha |
| 6 | No photographs, and no way to upload them | Photos, plus the Media Library | Vanessa, Development |
| 7 | No portfolio projects | At least a few real projects with client permission | Vanessa |
| 8 | Stand-in logo text and fonts | Official logo files; web-licensed John Mayer and Bayshore | Vanessa |
| 9 | No "forgot password" | Requires the email phase; until then a developer resets it | Development |
| 10 | Vanessa's phone and street address are in the public GitHub history | Make the repo private, or rewrite history | Samantha |
| 11 | Which email is public: the business card and business plan use a different address from Website Settings | Confirm and update Website Settings | Vanessa |

Blog and store placeholder pages still exist at their old addresses, but nothing links to them.

## 3. Placeholder content Vanessa must replace

Everything below can be changed in the admin area. Page Sections are listed by page name.

| Where in admin | What to review or replace |
|---|---|
| Homepage Content | Hero wording, introduction image, hero image |
| About Content | Her real story, portrait, speaking topics |
| Page Sections: About | Journey steps, philosophy, "coming soon" notices |
| About Values | The five value descriptions |
| Qualifications | Add real qualifications and memberships (none exist yet) |
| Services | All six: descriptions, "What's included", "Ideal for", images. Remove store wording from Photography |
| Page Sections: Service pages | "How it works" steps; specialised services; closing text |
| Portfolio Projects | Add real projects, with client permission and images |
| Collaborators | Add real collaborators, with the date they agreed |
| Page Sections: Portfolio pages | Page introductions and "coming soon" text |
| Page Sections: Contact | Introductions, location wording ("Visits are by appointment"), all six FAQ answers, including the budget answer |
| Legal Pages | Privacy Policy, Terms, Copyright |
| Speaking Engagements | Add real engagements |
| Website Settings | Confirm phone, email, city; add social links |

## 4. Hosting checklist

- [ ] Confirm Xneelo's MariaDB is 10.2.1 or newer, and PHP is 8.x
- [ ] Confirm whether command-line access is available
- [ ] Domain registered and pointing at Xneelo; HTTPS certificate active
- [ ] Add an HTTP-to-HTTPS redirect (sign-in needs HTTPS in production)
- [ ] Create the production database and a dedicated database user
- [ ] Put `.env` in the folder above the website with `APP_ENV=production`
- [ ] Run `database/setup.php` on the server (table names must not come from a Windows export)
- [ ] Create the real SuperAdmin with a strong password; no test accounts on the server
- [ ] Confirm `.htaccess` is honoured: `/.env`, `/database/` and `/includes/` must return 404
- [ ] Exclude `uploads/` from anything that mirrors files
- [ ] Schedule database and `uploads/` backups, and test a restore
- [ ] Add a monitoring check, and review `logs/php-error.log` after launch

## 5. Before going public

- [ ] Confirm the tagline style: "and" or "&", capitalised or not
- [ ] Decide whether the street address is ever shown
- [ ] Decide how long to keep login attempts, activity logs and IP addresses (POPIA)
- [ ] Favicon, sharing image, sitemap and robots file
- [ ] Cookie notice, if analytics is added, and update the Privacy Policy to match
- [ ] Real-device testing on phones, plus Safari and Firefox
- [ ] Screen reader and keyboard review
- [ ] Performance check once real photographs are in

## 6. Known limitations

- **Database values:** `PortfolioCategory` and `EnquiryType` still store `Colour Psychology` internally. Admin and public forms display the new name.
- **Menu changes for this phase:** Photography points to the portfolio gallery, Speaking to its service page, and Blog is removed. These need revisiting when those features are built.
- **Extra sections:** on service pages, a new section appears automatically. On Home, About and Services, order is fixed by the design.
- **Editors and live content:** an Editor changing an already Published record changes the site immediately. Only new records wait for an Admin.
- **Fallback text** is a saved copy of the seeded wording. Phone and email are not in it, so they don't show if the database is down.

## 7. Recommended route to launch

| Step | Scope | Needs |
|---|---|---|
| 1 | Make the repo private. Vanessa starts on her content | Nothing further |
| 2 | Media Library | Approval, GD switched on |
| 3 | **First public launch** as a brochure site, with enquiries by phone and email and the newsletter section unpublished | Photos, confirmed wording, legal review, hosting checklist |
| 4 | Email, contact form and newsletter | Approval, mail details |
| 5 | Blog | Approval |
| 6 | Photography store and PayFast | Approval, product decision, merchant account |
