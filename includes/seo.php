<?php
/**
 * Structured data for search engines and AI assistants (schema.org JSON-LD).
 * It is invisible on the page and tells them, in a form they read reliably:
 *
 *   - the business: name, what it does, where it works, contact details,
 *     services and social links (home, About, Contact and booking pages)
 *   - the person behind it, with qualifications and expertise (About page)
 *   - the website itself (home page)
 *   - each service, with the areas it is offered in (service pages)
 *   - each article, linked to its author (blog posts)
 *   - the breadcrumb trail (pages with breadcrumbs) and the FAQ (contact page)
 *
 * Everything comes from Website Settings, About Content, the services and the
 * articles, so it stays up to date. The street address is only included when
 * "Show the full street address" is switched on.
 */

declare(strict_types=1);

const SEO_BUSINESS_ID = '#business';
const SEO_PERSON_ID   = '#person';

/** Drop empty values, so nothing blank is announced. */
function seo_clean(array $data): array
{
    return array_filter($data, static fn($value): bool => $value !== null && $value !== '' && $value !== []);
}

/** Where the business works: Website Settings → Where you work, or the province. */
function seo_areas(): array
{
    $areas = function_exists('site_service_areas') ? site_service_areas() : [];
    if ($areas === [] && function_exists('site_settings')) {
        $province = trim((string) (site_settings()['Province'] ?? ''));
        $areas    = $province !== '' ? [$province] : [];
    }
    return $areas;
}

/** The first paragraph of a longer text. */
function seo_first_paragraph(?string $text): string
{
    $parts = preg_split('/\n\s*\n/', trim((string) $text)) ?: [];
    return trim((string) ($parts[0] ?? ''));
}

/** The designer's short bio: About Content → Short bio, or the biography's first paragraph. */
function seo_short_bio(): string
{
    $about = function_exists('site_about') ? site_about() : [];
    return trim((string) ($about['ShortBio'] ?? '')) ?: seo_first_paragraph($about['Biography'] ?? '');
}

/** The business, for the home, About, Contact and booking pages. */
function seo_business(): array
{
    $settings = function_exists('site_settings') ? site_settings() : [];
    $owner    = trim((string) (site_search_settings()['OwnerName'] ?? ''));
    $country  = trim((string) ($settings['Country'] ?? ''));
    $address  = seo_clean([
        '@type'           => 'PostalAddress',
        'addressLocality' => trim((string) ($settings['City'] ?? '')),
        'addressRegion'   => trim((string) ($settings['Province'] ?? '')),
        'addressCountry'  => strcasecmp($country, 'South Africa') === 0 ? 'ZA' : $country,
    ]);
    if (!empty($settings['ShowAddress'])) {
        $address = seo_clean($address + [
            'streetAddress' => trim(implode(', ', array_filter([$settings['AddressLine1'] ?? '', $settings['AddressLine2'] ?? '']))),
            'postalCode'    => trim((string) ($settings['PostalCode'] ?? '')),
        ]);
    }

    $services = function_exists('site_services') ? array_map(static fn(array $s): string => (string) $s['ServiceName'], site_services()) : [];
    $share    = function_exists('site_share_image') ? site_share_image() : null;

    return seo_clean([
        '@context'    => 'https://schema.org',
        '@type'       => 'ProfessionalService',
        '@id'         => absolute_url('') . SEO_BUSINESS_ID,
        'name'        => SITE_NAME,
        'slogan'      => site_tagline() ?: SITE_TAGLINE,
        'description' => seo_clean([seo_short_bio()])[0] ?? (site_tagline() ?: SITE_TAGLINE),
        'url'         => absolute_url(''),
        'logo'        => mail_origin() . asset('images/icon-512.png'),
        'image'       => $share ? mail_origin() . $share['url'] : null,
        'telephone'   => trim((string) ($settings['Phone'] ?? '')),
        'email'       => trim((string) ($settings['Email'] ?? '')),
        'address'     => count($address) > 1 ? $address : null,
        'areaServed'  => seo_areas(),
        'knowsAbout'  => $services,
        'founder'     => $owner !== '' ? ['@id' => absolute_url('about.php') . SEO_PERSON_ID] : null,
        'sameAs'      => seo_social($settings),
    ]);
}

function seo_social(array $settings): array
{
    $links = [];
    foreach (['Instagram', 'Facebook', 'LinkedIn', 'Pinterest'] as $network) {
        $link = trim((string) ($settings[$network] ?? ''));
        if (preg_match('#^https?://#i', $link)) {
            $links[] = $link;
        }
    }
    return $links;
}

/** The designer, for the About page: who she is, what she knows, her qualifications. */
function seo_person(): array
{
    $search = site_search_settings();
    $name   = trim((string) ($search['OwnerName'] ?? ''));
    if ($name === '') {
        return [];
    }
    $about    = function_exists('site_about') ? site_about() : [];
    $portrait = trim((string) ($about['PortraitImage'] ?? ''));

    $credentials = [];
    $schools     = [];
    foreach (function_exists('site_qualifications') ? site_qualifications() : [] as $q) {
        $credentials[] = seo_clean([
            '@type'              => 'EducationalOccupationalCredential',
            'name'               => (string) $q['Title'],
            'credentialCategory' => strtolower((string) ($q['Type'] ?? '')),
            'recognizedBy'       => trim((string) ($q['Institution'] ?? '')) !== '' ? ['@type' => 'Organization', 'name' => trim((string) $q['Institution'])] : null,
        ]);
        if (($q['Type'] ?? '') === 'Qualification' && trim((string) ($q['Institution'] ?? '')) !== '') {
            $schools[trim((string) $q['Institution'])] = ['@type' => 'EducationalOrganization', 'name' => trim((string) $q['Institution'])];
        }
    }
    $services = function_exists('site_services') ? array_map(static fn(array $s): string => (string) $s['ServiceName'], site_services()) : [];

    return seo_clean([
        '@context'      => 'https://schema.org',
        '@type'         => 'Person',
        '@id'           => absolute_url('about.php') . SEO_PERSON_ID,
        'name'          => $name,
        'jobTitle'      => trim((string) ($search['OwnerTitle'] ?? '')),
        'description'   => seo_short_bio(),
        'url'           => absolute_url('about.php'),
        'image'         => $portrait !== '' && link_is_safe($portrait) ? mail_origin() . image_url($portrait, 'web') : null,
        'worksFor'      => ['@id' => absolute_url('') . SEO_BUSINESS_ID],
        'knowsAbout'    => $services,
        'hasCredential' => $credentials,
        'alumniOf'      => array_values($schools),
        'sameAs'        => seo_social(function_exists('site_settings') ? site_settings() : []),
    ]);
}

/** The website, for the home page. */
function seo_website(): array
{
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'WebSite',
        'name'       => SITE_NAME,
        'url'        => absolute_url(''),
        'inLanguage' => 'en-ZA',
        'publisher'  => ['@id' => absolute_url('') . SEO_BUSINESS_ID],
    ];
}

/** One service, for its page. */
function seo_service(array $service): array
{
    $image = trim((string) ($service['FeaturedImage'] ?? ''));
    $url   = absolute_url('services/' . $service['Slug'] . '.php');
    return seo_clean([
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        '@id'         => $url . '#service',
        'name'        => (string) $service['ServiceName'],
        'serviceType' => (string) $service['ServiceName'],
        'description' => trim((string) ($service['ShortDescription'] ?? '')) ?: trim((string) ($service['Lead'] ?? '')),
        'url'         => $url,
        'image'       => $image !== '' && link_is_safe($image) ? mail_origin() . image_url($image, 'web') : null,
        'provider'    => ['@id' => absolute_url('') . SEO_BUSINESS_ID],
        'areaServed'  => seo_areas(),
    ]);
}

/** One blog article. */
function seo_article(array $post): array
{
    $image  = trim((string) ($post['FeaturedImage'] ?? ''));
    $author = trim((string) ($post['AuthorName'] ?? ''));
    $owner  = trim((string) (site_search_settings()['OwnerName'] ?? ''));
    return seo_clean([
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => mb_substr((string) $post['Title'], 0, 110),
        'description'      => trim((string) ($post['Excerpt'] ?? '')),
        'image'            => $image !== '' && link_is_safe($image) ? mail_origin() . image_url($image, 'web') : null,
        'datePublished'    => !empty($post['PublishedDate']) ? date('c', strtotime((string) $post['PublishedDate'])) : null,
        'dateModified'     => !empty($post['UpdatedDate']) ? date('c', strtotime((string) $post['UpdatedDate'])) : null,
        'inLanguage'       => 'en-ZA',
        // The designer's articles point to her About page, which says who she is.
        'author'           => $author === '' ? null : ($owner !== '' && strcasecmp($author, $owner) === 0
            ? ['@type' => 'Person', '@id' => absolute_url('about.php') . SEO_PERSON_ID, 'name' => $author, 'url' => absolute_url('about.php')]
            : ['@type' => 'Person', 'name' => $author]),
        'publisher'        => ['@type' => 'Organization', '@id' => absolute_url('') . SEO_BUSINESS_ID, 'name' => SITE_NAME, 'logo' => ['@type' => 'ImageObject', 'url' => mail_origin() . asset('images/icon-512.png')]],
        'articleSection'   => $post['CategoryName'] ?? null,
        'mainEntityOfPage' => absolute_url('blog/blog-post.php?slug=' . $post['Slug']),
    ]);
}

/** Breadcrumbs: [label, page or null for the current page] pairs, from the home page down. */
function seo_breadcrumbs(array $trail): array
{
    $items = [];
    foreach (array_values($trail) as $index => [$label, $path]) {
        $items[] = seo_clean([
            '@type'    => 'ListItem',
            'position' => $index + 1,
            'name'     => trim((string) $label) ?: SITE_NAME,
            'item'     => $path !== null ? absolute_url($path === 'index.php' ? '' : $path) : null,
        ]);
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

/** Questions and answers (the contact page FAQ). Items have Title (the question) and Text (the answer). */
function seo_faq(array $items): array
{
    $questions = [];
    foreach ($items as $item) {
        $question = trim((string) ($item['Title'] ?? ''));
        $answer   = trim((string) ($item['Text'] ?? ''));
        if ($question !== '' && $answer !== '') {
            $questions[] = ['@type' => 'Question', 'name' => $question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer]];
        }
    }
    return $questions === [] ? [] : ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $questions];
}

/** A <script> block for the page. Escaped so no text can end the script early. */
function seo_script(array $data): string
{
    if ($data === []) {
        return '';
    }
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE)
         . "</script>\n";
}
