# Launch Readiness

**Verdict: not ready for public launch.** The content system and the Home, About and Services pages are working. The blockers are unfinished linked pages, missing legal pages, no public way to make contact, and content Vanessa still has to confirm.

## 1. Status by area

| Area | Status | Notes |
|---|---|---|
| Home, About, Services, footer | Ready, pending wording | Read from the CMS, with fallback text if the database is down |
| Admin sign-in, roles, activity log | Ready | Tested on the local stack and Apache |
| CMS editing of mapped content | Ready | Website Settings, Homepage, About, Values, Qualifications, Page Sections, Services, Speaking |
| Other public pages | **Not built** | 29 placeholder pages, most of them linked from the menus |
| Legal pages | **Not built** | Privacy Policy, Terms and Copyright are empty placeholders |
| Contact and newsletter | **Not built** | Awaiting approval. The sign-up form saves nothing |
| Images and media | **Not built** | No uploads. Image paths are typed by hand |
| Photography store, PayFast, blog CMS, email | Not built | Awaiting approval |
| Production hosting | **Not tested** | Only tested locally |

## 2. Launch blockers

| # | Blocker | What's needed | Owner |
|---|---|---|---|
| 1 | Menus and buttons lead to placeholder pages: Contact (1), Portfolio (6), Speaking (1), Photography (7), Blog (5), individual services (6) | Build them, or hide their links for launch | Development |
| 2 | Privacy Policy, Terms and Copyright are placeholders. POPIA requires a privacy notice | Legal wording, Information Officer name, a Site Pages edit screen, and public pages | Vanessa, Development |
| 3 | No way for visitors to get in touch: the phone and email in Website Settings aren't shown anywhere | At minimum, show them on a built Contact page | Development |
| 4 | The newsletter form says sign-ups are opening soon and saves nothing | Build it, or remove the section for launch | Development |
| 5 | All wording is draft, written from brand documents | Vanessa's review (`CONTENT_REQUIREMENTS.md`, section 8) | Vanessa |
| 6 | Qualifications are a "coming soon" notice. The story has no real personal details | Vanessa's details | Vanessa |
| 7 | No photographs; decorative panels stand in everywhere | Photos, plus the Media Library so she can upload them | Vanessa, Development |
| 8 | Stand-in logo text and fonts | Official logo files; web-licensed John Mayer and Bayshore | Vanessa |
| 9 | No "forgot password". A locked-out user needs a developer | Requires the email phase | Development |
| 10 | Vanessa's phone and street address are in the public GitHub history | Make the repo private, or rewrite history | Samantha |

## 3. Hosting checklist

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

## 4. Before going public

**Content and decisions**

- [ ] Vanessa completes `CONTENT_REQUIREMENTS.md`
- [ ] Confirm the tagline style: "and" or "&", capitalised or not
- [ ] Confirm which email address is public, and where enquiries go
- [ ] Decide whether the street address is ever shown
- [ ] Decide how long to keep login attempts, activity logs and IP addresses (POPIA)

**Site quality**

- [ ] Favicon, sharing image and a designed 404 page
- [ ] Sitemap, robots file and search descriptions for each page
- [ ] Cookie notice, if analytics is added
- [ ] Real-device testing on phones, plus Safari and Firefox
- [ ] Screen reader and keyboard review
- [ ] Performance check once real photographs are in

## 5. Known limitations

- **Old service name in two places.** `PortfolioProjects.PortfolioCategory` and `ContactMessages.EnquiryType` still list `Colour Psychology` as an allowed value in the database. Visitors don't see these yet. They need a display label or a migration when the portfolio and contact phases are built. `README.md` and `CONTENT_REQUIREMENTS.md` also still use the old name.
- **Service web address.** It stays `colour-psychology`, so links keep working.
- **Section order is fixed by each page's design.** New items appear automatically; a brand-new section needs a small template change.
- **Editors and live content.** An Editor changing an already Published record changes the site immediately. Only new records wait for an Admin.
- **Site-wide settings.** The business name, phone, email and social links can be edited, but pages don't use them yet. Only the tagline is connected.
- **Fallback text** is a saved copy. It does not update when the CMS changes.

## 6. Recommended route to launch

| Step | Scope | Needs |
|---|---|---|
| 1 | Make the repo private. Vanessa starts on her content | Nothing further |
| 2 | Media Library, service detail pages, legal pages, contact details page | Approval, legal wording |
| 3 | **First public launch** as a brochure site: Home, About, Services, service pages, Portfolio, legal, and contact details. Hide or remove Blog, Store and the newsletter form | Photos, confirmed wording, hosting checklist |
| 4 | Email, contact form and newsletter | Approval, mail details |
| 5 | Blog | Approval |
| 6 | Photography store and PayFast | Approval, product decision, merchant account |

This gets the business online soonest without waiting for the features that depend on outside decisions.
