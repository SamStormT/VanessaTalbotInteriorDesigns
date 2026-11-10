# Content Mapping

Every visible content block on the Home, About and Services pages, and the database record that holds it.

Home, About, Services and the footer now **read from the database**. All wording is still draft until Vanessa confirms it (`CONTENT_REQUIREMENTS.md`, section 8). If the database can't be reached, the pages show the snapshot in `includes/content-fallback.php`.

**Reading the Record column**

| Format | Meaning | Example |
|---|---|---|
| `page/section` | A row in `PageSections`, found by `PageKey` and `SectionKey` | `home/introduction` |
| `page/section#item` | A row in `PageSectionItems`, found by its section and `ItemKey` | `home/introduction#art` |
| `Row 1` | The single row in a one-row table | `HomePageContent`, row 1 |
| `Each service` | One row per service in `Services`, found by `Slug` | |

Fields marked *(empty)* exist but have nothing to hold yet.

## 1. Home

| Page | Section | Content block | Database table | Record | Field(s) |
|---|---|---|---|---|---|
| Home | Hero | Small heading | `HomePageContent` | Row 1 | `HeroEyebrow` (see section 7) |
| Home | Hero | Title | `HomePageContent` | Row 1 | `HeroTitle` |
| Home | Hero | Tagline | `WebsiteSettings` | Row 1 | `Tagline` |
| Home | Hero | Paragraph | `HomePageContent` | Row 1 | `HeroSubtitle` |
| Home | Hero | First button | `HomePageContent` | Row 1 | `CTA1Text`, `CTA1Link` |
| Home | Hero | Second button | `HomePageContent` | Row 1 | `CTA2Text`, `CTA2Link` |
| Home | Hero | Image | `HomePageContent` | Row 1 | `HeroImage`, `HeroImageAlt` *(empty)* |
| Home | Featured call to action | Title | `HomePageContent` | Row 1 | `FeaturedCTATitle` |
| Home | Featured call to action | Paragraph | `HomePageContent` | Row 1 | `FeaturedCTAText` |
| Home | Featured call to action | Button link | `HomePageContent` | Row 1 | `FeaturedCTALink` |
| Home | Featured call to action | Button text | `PageSections` | `home/feature-cta` | `ButtonText` |
| Home | Meet Vanessa | Small heading | `PageSections` | `home/introduction` | `Eyebrow` |
| Home | Meet Vanessa | Heading | `PageSections` | `home/introduction` | `Heading` |
| Home | Meet Vanessa | Opening sentence | `PageSections` | `home/introduction` | `Introduction` |
| Home | Meet Vanessa | Paragraph | `PageSections` | `home/introduction` | `Body` |
| Home | Meet Vanessa | Link to About | `PageSections` | `home/introduction` | `ButtonText`, `ButtonLink` |
| Home | Meet Vanessa | Art | `PageSectionItems` | `home/introduction#art` | `Title`, `Text`, `Icon` |
| Home | Meet Vanessa | Science | `PageSectionItems` | `home/introduction#science` | `Title`, `Text`, `Icon` |
| Home | Meet Vanessa | Nature | `PageSectionItems` | `home/introduction#nature` | `Title`, `Text`, `Icon` |
| Home | Meet Vanessa | Portrait | `HomePageContent` | Row 1 | `IntroductionImage`, `IntroductionImageAlt` *(empty)* |
| Home | Quote | Quote | `HomePageContent` | Row 1 | `FeaturedQuote` |
| Home | Quote | Author | `HomePageContent` | Row 1 | `FeaturedQuoteAuthor` |
| Home | Services overview | Small heading | `PageSections` | `home/services` | `Eyebrow` |
| Home | Services overview | Heading | `PageSections` | `home/services` | `Heading` |
| Home | Services overview | Paragraph | `PageSections` | `home/services` | `Introduction` |
| Home | Services overview | Button | `PageSections` | `home/services` | `ButtonText`, `ButtonLink` |
| Home | Services overview | Six cards | `Services` | Each service | `ServiceName`, `ShortDescription`, `Icon`, `Slug`, `DisplayOrder` |
| Home | Featured portfolio | Small heading | `PageSections` | `home/portfolio` | `Eyebrow` |
| Home | Featured portfolio | Heading | `PageSections` | `home/portfolio` | `Heading` |
| Home | Featured portfolio | Paragraph | `PageSections` | `home/portfolio` | `Introduction` |
| Home | Featured portfolio | Link to portfolio | `PageSections` | `home/portfolio` | `ButtonText`, `ButtonLink` |
| Home | Featured portfolio | Biophilic Interior Design tile | `PageSectionItems` | `home/portfolio#biophilic-interior-design` | `Title`, `Text`, `Icon`, `Link` |
| Home | Featured portfolio | Colour & Design Psychology tile | `PageSectionItems` | `home/portfolio#colour-psychology` | `Title`, `Text`, `Icon`, `Link` |
| Home | Featured portfolio | Photography Gallery tile | `PageSectionItems` | `home/portfolio#photography-gallery` | `Title`, `Text`, `Icon`, `Link` |
| Home | Latest blog | Small heading | `PageSections` | `home/blog` | `Eyebrow` |
| Home | Latest blog | Heading | `PageSections` | `home/blog` | `Heading` |
| Home | Latest blog | Posts, once written | `BlogPosts` | Latest published | `Title`, `Excerpt`, `Slug`, `PublishedDate` *(empty)* |
| Home | Latest blog, no posts yet | Message heading | `PageSections` | `home/blog-empty` | `Heading` |
| Home | Latest blog, no posts yet | Message | `PageSections` | `home/blog-empty` | `Introduction` |
| Home | Latest blog, no posts yet | Topic: Biophilic design | `PageSectionItems` | `home/blog-empty#biophilic-design` | `Title` |
| Home | Latest blog, no posts yet | Topic: Colour & design psychology | `PageSectionItems` | `home/blog-empty#colour-psychology` | `Title` |
| Home | Latest blog, no posts yet | Topic: Wellbeing at home | `PageSectionItems` | `home/blog-empty#wellbeing-at-home` | `Title` |
| Home | Latest blog, no posts yet | Topic: Healthier workspaces | `PageSectionItems` | `home/blog-empty#healthier-workspaces` | `Title` |
| Home | Latest blog, no posts yet | Topic: Photography | `PageSectionItems` | `home/blog-empty#photography` | `Title` |
| Home | Newsletter | Small heading | `PageSections` | `home/newsletter` | `Eyebrow` |
| Home | Newsletter | Heading | `PageSections` | `home/newsletter` | `Heading` |
| Home | Newsletter | Paragraph | `PageSections` | `home/newsletter` | `Introduction` |
| Home | Newsletter | Privacy note | `PageSections` | `home/newsletter` | `Body` |
| Home | Closing call to action | Heading | `PageSections` | `home/contact-cta` | `Heading` |
| Home | Closing call to action | Paragraph | `PageSections` | `home/contact-cta` | `Introduction` |
| Home | Closing call to action | First button | `PageSections` | `home/contact-cta` | `ButtonText`, `ButtonLink` |
| Home | Closing call to action | Second button | `PageSections` | `home/contact-cta` | `SecondaryButtonText`, `SecondaryButtonLink` |
| Home | Closing call to action | Residential homes | `PageSectionItems` | `home/contact-cta#residential-homes` | `Title` |
| Home | Closing call to action | Workspaces | `PageSectionItems` | `home/contact-cta#workspaces` | `Title` |
| Home | Closing call to action | Health & wellness centres | `PageSectionItems` | `home/contact-cta#health-wellness-centres` | `Title` |
| Home | Closing call to action | Boutiques | `PageSectionItems` | `home/contact-cta#boutiques` | `Title` |
| Home | Closing call to action | Events | `PageSectionItems` | `home/contact-cta#events` | `Title` |
| Home | Search engines | Page description | `HomePageContent` | Row 1 | `MetaDescription` |
| Home | Search engines | Page title | `HomePageContent` | Row 1 | `MetaTitle` *(empty; built from the business name and tagline)* |

## 2. About

| Page | Section | Content block | Database table | Record | Field(s) |
|---|---|---|---|---|---|
| About | Page heading | Title | `PageSections` | `about/page-header` | `Heading` |
| About | Page heading | Tagline | `WebsiteSettings` | Row 1 | `Tagline` |
| About | Vanessa's story | Small heading | `PageSections` | `about/story` | `Eyebrow` |
| About | Vanessa's story | Heading | `PageSections` | `about/story` | `Heading` |
| About | Vanessa's story | Opening sentence and two paragraphs | `AboutContent` | Row 1 | `Biography` |
| About | Vanessa's story | Portrait | `AboutContent` | Row 1 | `PortraitImage`, `PortraitImageAlt` *(empty)* |
| About | Journey | Small heading | `PageSections` | `about/journey` | `Eyebrow` |
| About | Journey | Heading | `PageSections` | `about/journey` | `Heading` |
| About | Journey | Paragraph | `PageSections` | `about/journey` | `Introduction` |
| About | Journey | A scientific foundation | `PageSectionItems` | `about/journey#science` | `Title`, `Text`, `Icon` |
| About | Journey | A creative calling | `PageSectionItems` | `about/journey#design` | `Title`, `Text`, `Icon` |
| About | Journey | Nature as the bridge | `PageSectionItems` | `about/journey#nature` | `Title`, `Text`, `Icon` |
| About | Philosophy | Small heading | `PageSections` | `about/philosophy` | `Eyebrow` |
| About | Philosophy | Heading | `PageSections` | `about/philosophy` | `Heading` |
| About | Philosophy | Paragraph | `PageSections` | `about/philosophy` | `Introduction` |
| About | Philosophy | "In practice…" line | `PageSections` | `about/philosophy` | `Body` |
| About | Philosophy | Light and air | `PageSectionItems` | `about/philosophy#light-and-air` | `Title`, `Text` |
| About | Philosophy | Living elements | `PageSectionItems` | `about/philosophy#living-elements` | `Title`, `Text` |
| About | Philosophy | Natural materials | `PageSectionItems` | `about/philosophy#natural-materials` | `Title`, `Text` |
| About | Philosophy | Colour with purpose | `PageSectionItems` | `about/philosophy#colour-with-purpose` | `Title`, `Text` |
| About | Philosophy | Organic form | `PageSectionItems` | `about/philosophy#organic-form` | `Title`, `Text` |
| About | Philosophy | Quote and author | `PageSectionItems` | `about/quote#kellert` | `Text`, `Title` |
| About | Values | Small heading | `PageSections` | `about/values` | `Eyebrow` |
| About | Values | Heading | `PageSections` | `about/values` | `Heading` |
| About | Values | Introduction | `AboutContent` | Row 1 | `CoreValues` *(empty; the page has none)* |
| About | Values | Five values | `AboutValues` | One row each, by `Title` | `Title`, `Description`, `Icon`, `DisplayOrder` |
| About | Qualifications | Small heading | `PageSections` | `about/qualifications` | `Eyebrow` |
| About | Qualifications | Heading | `PageSections` | `about/qualifications` | `Heading` |
| About | Qualifications | Paragraph | `AboutContent` | Row 1 | `Qualifications` |
| About | Qualifications | "Full details coming soon" heading | `PageSections` | `about/qualifications-notice` | `Heading` |
| About | Qualifications | "Full details coming soon" text | `PageSections` | `about/qualifications-notice` | `Introduction`, `ButtonLink` |
| About | Qualifications | Qualifications, once confirmed | `AboutQualifications` | One row each | `Title`, `Type`, `Institution`, `YearCompleted` *(empty)* |
| About | Speaking | Small heading | `PageSections` | `about/speaking` | `Eyebrow` |
| About | Speaking | Heading | `PageSections` | `about/speaking` | `Heading` |
| About | Speaking | Paragraph | `PageSections` | `about/speaking` | `Introduction` |
| About | Speaking | "Talks are tailored…" line | `PageSections` | `about/speaking` | `Body` |
| About | Speaking | Four talk topics | `AboutContent` | Row 1 | `SpeakingTopics` |
| About | Speaking | "Will be listed here soon" note | `PageSections` | `about/speaking-notice` | `Introduction` |
| About | Speaking | Engagements, once confirmed | `SpeakingEngagements` | One row each | `Title`, `Venue`, `EventDate` *(empty)* |
| About | Speaking | First button | `PageSections` | `about/speaking` | `ButtonText`, `ButtonLink` |
| About | Speaking | Second button | `PageSections` | `about/speaking` | `SecondaryButtonText`, `SecondaryButtonLink` |
| About | Closing call to action | Heading | `PageSections` | `about/closing` | `Heading` |
| About | Closing call to action | Paragraph | `PageSections` | `about/closing` | `Introduction` |
| About | Closing call to action | First button | `PageSections` | `about/closing` | `ButtonText`, `ButtonLink` |
| About | Closing call to action | Second button | `PageSections` | `about/closing` | `SecondaryButtonText`, `SecondaryButtonLink` |
| About | Search engines | Page title | `AboutContent` | Row 1 | `MetaTitle` |
| About | Search engines | Page description | `AboutContent` | Row 1 | `MetaDescription` |

## 3. Services

| Page | Section | Content block | Database table | Record | Field(s) |
|---|---|---|---|---|---|
| Services | Page heading | Title | `PageSections` | `services/page-header` | `Heading` |
| Services | Page heading | Paragraph | `PageSections` | `services/page-header` | `Introduction` |
| Services | Jump links | Name and anchor | `Services` | Each service | `ServiceName`, `Slug`, `Icon` |
| Services | Each of the six services | Heading | `Services` | Each service | `ServiceName` |
| Services | Each of the six services | Order on the page | `Services` | Each service | `DisplayOrder` |
| Services | Each of the six services | Opening sentence | `Services` | Each service | `Lead` |
| Services | Each of the six services | Main paragraph | `Services` | Each service | `FullDescription` |
| Services | Each of the six services | "What's included" list | `Services` | Each service | `IncludedItems` |
| Services | Each of the six services | "Ideal for" tags | `Services` | Each service | `IdealFor` |
| Services | Each of the six services | Visibility | `Services` | Each service | `Status` |
| Services | Each of the six services | Image | `Services` | Each service | `FeaturedImage`, `FeaturedImageAlt` *(empty)* |
| Services | Each of the six services | Search engine details | `Services` | Each service | `MetaTitle`, `MetaDescription` *(empty)* |
| Services | How it works | Small heading | `PageSections` | `services/process` | `Eyebrow` |
| Services | How it works | Heading | `PageSections` | `services/process` | `Heading` |
| Services | How it works | Listen | `PageSectionItems` | `services/process#listen` | `Title`, `Text` |
| Services | How it works | Explore | `PageSectionItems` | `services/process#explore` | `Title`, `Text` |
| Services | How it works | Design | `PageSectionItems` | `services/process#design` | `Title`, `Text` |
| Services | How it works | Bring to life | `PageSectionItems` | `services/process#bring-to-life` | `Title`, `Text` |
| Services | Closing call to action | Heading | `PageSections` | `services/closing` | `Heading` |
| Services | Closing call to action | Paragraph | `PageSections` | `services/closing` | `Introduction` |
| Services | Closing call to action | First button | `PageSections` | `services/closing` | `ButtonText`, `ButtonLink` |
| Services | Closing call to action | Second button | `PageSections` | `services/closing` | `SecondaryButtonText`, `SecondaryButtonLink` |
| Services | Search engines | Browser title | `PageSections` | `services/seo` | `Heading` |
| Services | Search engines | Page description | `PageSections` | `services/seo` | `Introduction` |

## 4. Footer (shared by every page)

| Page | Section | Content block | Database table | Record | Field(s) |
|---|---|---|---|---|---|
| All pages | Footer | Tagline | `WebsiteSettings` | Row 1 | `Tagline` |
| All pages | Footer | Short description | `PageSections` | `global/footer` | `Introduction` |
| All pages | Footer | "Ready to bring nature…" text | `PageSections` | `global/footer` | `Body` |
| All pages | Footer | Contact button | `PageSections` | `global/footer` | `ButtonText`, `ButtonLink` |

## 5. The six combined rows, resolved

| Previously in | Text | Now in |
|---|---|---|
| `HomePageContent.IntroductionText` | Opening sentence, paragraph | `home/introduction`: `Introduction`, `Body` |
| `AboutContent.Journey` | Introduction | `about/journey`: `Introduction` |
| `AboutContent.Journey` | Third step | `about/journey#nature`: `Text` |
| `AboutContent.Philosophy` | Paragraph, "In practice…" line | `about/philosophy`: `Introduction`, `Body` |
| `AboutContent.Philosophy` | Five principles | Five items in `about/philosophy` |
| `AboutContent.SpeakingIntroduction` | Paragraph, "Talks are tailored…" line | `about/speaking`: `Introduction`, `Body` |

Steps 1 and 2 of the journey moved from `AboutContent.ScienceBackground` and `DesignBackground` into `about/journey#science` and `#design`, so that all three steps work the same way.

Those six database columns are now empty and hidden from the admin forms, so each piece of text has one home. The columns themselves remain.

## 6. What stays in the code

These are interface wording and site navigation rather than content, so they are not in the database:

- Main menu, footer menus and their headings, breadcrumbs, logo text and "Skip to content".
- "Learn more", "What's included", "Ideal for", "More about…", "Enquire about…" and the "01 Service" numbering.
- Newsletter form labels and the Subscribe button.
- Contact form labels, error messages and the "message sent" confirmation; newsletter, confirmation and unsubscribe messages; sign-in and password reset screens.
- Email wording (`includes/mail-templates/`). Where messages are sent is set in Website Settings.
- The footer copyright line and legal link names.

A comparison of the live pages with the database confirmed that these are the only visible words not held in the database.

## 7. Verification

- Before connecting, the Home, About, Services and Contact page output was captured.
- After connecting, on a clean install, all four are identical, apart from whitespace and HTML comments, both from the database and in fallback mode with the database unreachable.
- The local database now matches as well, including the corrected hero heading.


## 8. Service, portfolio, contact and legal pages

Page keys follow `PageSections.PageKey`. Sections are listed in the admin area under Page Sections.

| Page | Section | Content block | Database table | Record | Field(s) |
|---|---|---|---|---|---|
| Each service page | Heading | Title and opening sentence | `Services` | Each service | `ServiceName`, `Lead` |
| Each service page | Main | Description, included list, ideal for, image | `Services` | Each service | `FullDescription`, `IncludedItems`, `IdealFor`, `FeaturedImage`, `FeaturedImageAlt` |
| Each service page | How it works | Heading and steps | `PageSections`, `PageSectionItems` | `service-{slug}/approach` | Heading fields; item `Title`, `Text`, `Icon` |
| Biophilic Design | Specialised services | Heading and items | `PageSections`, `PageSectionItems` | `service-biophilic-design/specialised` | Heading fields; item `Title`, `Text`, `Icon` |
| Each service page | Closing | Heading, text, buttons | `PageSections` | `service-{slug}/closing` | `Heading`, `Introduction`, button fields |
| Portfolio | Heading | Title and text | `PageSections` | `portfolio/page-header` | `Heading`, `Introduction` |
| Portfolio | Categories | Four tiles | `PageSections`, `PageSectionItems` | `portfolio/categories` | Heading fields; item `Title`, `Text`, `Icon`, `Link`, `Image` |
| Portfolio | Featured | Heading and projects | `PageSections`, `PortfolioProjects` | `portfolio/featured`; `IsFeatured` projects | Heading fields; project fields |
| Portfolio categories | Heading, intro, empty notice, closing | Wording | `PageSections` | `portfolio-{category}/page-header`, `intro`, `empty`, `closing` | Heading and button fields |
| Portfolio categories | Projects | Project tiles | `PortfolioProjects` | By `PortfolioCategory` | `Title`, `Slug`, `Location`, `FeaturedImage` |
| Single project | Whole page | Details and gallery | `PortfolioProjects`, `ProjectImages` | By `Slug` | Project fields; `ImagePath`, `AltText`, `Caption` |
| Collaborators | Wording | Heading, intro, empty notice, closing | `PageSections` | `portfolio-collaborators/*` | Heading and button fields |
| Collaborators | People | Cards | `Collaborators` | One row each | `Name`, `RoleTitle`, `Description`, `Logo`, `WebsiteLink` |
| Contact | Heading | Title and text | `PageSections` | `contact/page-header` | `Heading`, `Introduction` |
| Contact | Details | Heading and text | `PageSections` | `contact/details` | Heading fields |
| Contact | Details | Phone, email, social links | `WebsiteSettings` | Row 1 | `Phone`, `Email`, `Facebook`, `Instagram`, `LinkedIn`, `Pinterest` |
| Contact | Location | Heading and text | `PageSections` | `contact/location` | Heading fields |
| Contact | Location | Town and address | `WebsiteSettings` | Row 1 | `City`, `Province`, `Country`; street lines only if `ShowAddress` is on |
| Contact | Form | Heading and text | `PageSections` | `contact/form` | Heading fields (form labels stay in code) |
| Contact | FAQ | Questions and answers | `PageSections`, `PageSectionItems` | `contact/faq` | Item `Title` (question), `Text` (answer) |
| Legal pages | Whole page | Title and wording | `SitePages` | By `Slug` | `Title`, `Content`, `MetaDescription` |
