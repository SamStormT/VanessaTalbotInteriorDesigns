<?php
/**
 * Structured data for search engines and AI assistants (schema.org JSON-LD):
 * who the business is, where it works and what it offers, and for each blog
 * article its title, date, author and picture. It is invisible on the page.
 *
 * Everything comes from Website Settings, the published services and the
 * article itself, so it stays up to date. The street address is only
 * included when "Show the full street address" is switched on.
 */

declare(strict_types=1);

/** The business, for the home, About, Contact and booking pages. */
function seo_business(): array
{
    $settings = function_exists('site_settings') ? site_settings() : [];
    $country  = trim((string) ($settings['Country'] ?? ''));
    $address  = array_filter([
        '@type'           => 'PostalAddress',
        'addressLocality' => trim((string) ($settings['City'] ?? '')),
        'addressRegion'   => trim((string) ($settings['Province'] ?? '')),
        'addressCountry'  => strcasecmp($country, 'South Africa') === 0 ? 'ZA' : $country,
    ]);
    if (!empty($settings['ShowAddress'])) {
        $address['streetAddress'] = trim(implode(', ', array_filter([$settings['AddressLine1'] ?? '', $settings['AddressLine2'] ?? ''])));
        $address['postalCode']    = trim((string) ($settings['PostalCode'] ?? ''));
        $address = array_filter($address);
    }

    $social = [];
    foreach (['Instagram', 'Facebook', 'LinkedIn', 'Pinterest'] as $network) {
        $link = trim((string) ($settings[$network] ?? ''));
        if (preg_match('#^https?://#i', $link)) {
            $social[] = $link;
        }
    }
    $services = function_exists('site_services') ? array_map(static fn(array $s): string => (string) $s['ServiceName'], site_services()) : [];
    $share    = function_exists('site_share_image') ? site_share_image() : null;
    $area     = trim((string) ($settings['Province'] ?? '')) ?: trim((string) ($settings['City'] ?? ''));

    return array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'ProfessionalService',
        '@id'         => absolute_url('') . '#business',
        'name'        => SITE_NAME,
        'description' => site_tagline() ?: SITE_TAGLINE,
        'url'         => absolute_url(''),
        'logo'        => mail_origin() . asset('images/icon-512.png'),
        'image'       => $share ? mail_origin() . $share['url'] : null,
        'telephone'   => trim((string) ($settings['Phone'] ?? '')),
        'email'       => trim((string) ($settings['Email'] ?? '')),
        'address'     => count($address) > 1 ? $address : null,
        'areaServed'  => $area !== '' ? $area : null,
        'knowsAbout'  => $services ?: null,
        'sameAs'      => $social ?: null,
    ], static fn($value): bool => $value !== null && $value !== '' && $value !== []);
}

/** One blog article. */
function seo_article(array $post): array
{
    $image = trim((string) ($post['FeaturedImage'] ?? ''));
    return array_filter([
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => mb_substr((string) $post['Title'], 0, 110),
        'description'      => trim((string) ($post['Excerpt'] ?? '')),
        'image'            => $image !== '' && link_is_safe($image) ? mail_origin() . image_url($image, 'web') : null,
        'datePublished'    => !empty($post['PublishedDate']) ? date('c', strtotime((string) $post['PublishedDate'])) : null,
        'dateModified'     => !empty($post['UpdatedDate']) ? date('c', strtotime((string) $post['UpdatedDate'])) : null,
        'author'           => trim((string) ($post['AuthorName'] ?? '')) !== '' ? ['@type' => 'Person', 'name' => trim((string) $post['AuthorName'])] : null,
        'publisher'        => ['@type' => 'Organization', 'name' => SITE_NAME, 'logo' => ['@type' => 'ImageObject', 'url' => mail_origin() . asset('images/icon-512.png')]],
        'articleSection'   => $post['CategoryName'] ?? null,
        'mainEntityOfPage' => absolute_url('blog/blog-post.php?slug=' . $post['Slug']),
    ], static fn($value): bool => $value !== null && $value !== '');
}

/** A <script> block for the page head. Escaped so no text can end the script early. */
function seo_script(array $data): string
{
    return '<script type="application/ld+json">'
         . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE)
         . "</script>\n";
}
