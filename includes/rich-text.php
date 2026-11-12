<?php
/**
 * Article formatting for blog posts and newsletters.
 *
 * Plain text keeps working: paragraphs are separated by a blank line. On top
 * of that, a few simple marks are understood (the editor's toolbar inserts
 * them, so nobody has to remember them):
 *
 *   ## Heading                 ### Smaller heading
 *   **bold**                   _italic_
 *   - item  (or * item, • item; 1. item for numbers), no blank line needed
 *   [link text](contact.php)   or a full https:// address; bare addresses link too
 *   > A pull quote             > — Who said it
 *   [[image uploads/media/2026/11/photo.jpg | Optional caption]]
 *   [[button Book a consultation | contact.php]]
 *   [[section green]] ... [[/section]]     styles: peach, green, gold, white
 *   [[more]]                   newsletters show the article up to here
 *
 * Everything is escaped. Links must be site pages, https, mailto or tel.
 * Images must be in the Media Library. The same text renders for the website
 * (classes, styled by CSS) and for email (inline styles, full addresses).
 */

declare(strict_types=1);

const RICH_SECTION_STYLES = ['peach', 'green', 'gold', 'white'];

/**
 * Render formatted text as HTML. $mode is 'web' or 'email'. In email,
 * $background ('green' or 'gold') makes the text light for a dark panel.
 */
function rich_render(?string $text, string $mode = 'web', string $background = ''): string
{
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $text));
    return rich_blocks($lines, $mode, $background);
}

/**
 * The part of an article a newsletter shows: everything before [[more]], or
 * if there is no marker, the opening paragraphs (up to about 120 words).
 * Returns formatted text, not HTML.
 */
function rich_preview(?string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
    if (preg_match('/^\[\[more\]\]\s*$/m', $text, $match, PREG_OFFSET_CAPTURE)) {
        return trim(substr($text, 0, $match[0][1]));
    }

    $kept  = [];
    $words = 0;
    foreach (preg_split('/\n\s*\n/', $text) ?: [] as $block) {
        $block = trim($block);
        if ($block === '' || str_starts_with($block, '[[') || str_starts_with($block, '#') || str_starts_with($block, '>')) {
            if ($kept !== []) {
                break;
            }
            continue;
        }
        $kept[] = $block;
        $words += str_word_count($block);
        if ($words >= 120 || count($kept) >= 2) {
            break;
        }
    }
    return implode("\n\n", $kept);
}

/** The first pull quote in an article, as [quote, author], or null. */
function rich_first_quote(?string $text): ?array
{
    $quote  = [];
    $author = '';
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $text)) as $line) {
        if (preg_match('/^\s*>\s?(.*)$/', $line, $m)) {
            if (preg_match('/^(?:—|–|--|-)\s*(.+)$/u', trim($m[1]), $who)) {
                $author = trim($who[1]);
            } elseif (trim($m[1]) !== '') {
                $quote[] = trim($m[1]);
            }
        } elseif ($quote !== []) {
            break;
        }
    }
    return $quote === [] ? null : [implode(' ', $quote), $author];
}

/** Plain-text version (for the text part of emails). */
function rich_plain(?string $text): string
{
    $out = [];
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $text)) as $line) {
        $t = trim($line);
        if (preg_match('/^\[\[(?:\/?section[^\]]*|more)\]\]$/', $t)) {
            continue;
        }
        if (preg_match('/^\[\[image\s+([^\]|]+?)\s*(?:\|\s*(.*?))?\]\]$/', $t, $m)) {
            $t = trim((string) ($m[2] ?? '')) !== '' ? '[Image: ' . trim($m[2]) . ']' : '';
        } elseif (preg_match('/^\[\[button\s+(.+?)\s*\|\s*(\S+?)\s*\]\]$/', $t, $m)) {
            $t = $m[1] . ': ' . rich_href($m[2], 'email');
        } else {
            $t = preg_replace('/^#{2,3}\s+/', '', $t);
            $t = preg_replace('/^>\s?/', '  ', $t);
            $t = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static fn($m) => $m[1] . ' (' . rich_href($m[2], 'email') . ')', $t);
            $t = preg_replace('/\*\*(.+?)\*\*/', '$1', $t);
            $t = preg_replace('/(?<![\w])_(.+?)_(?![\w])/', '$1', $t);
        }
        $out[] = $t;
    }
    return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $out)));
}

// ---------------------------------------------------------------------------
// Block parser
// ---------------------------------------------------------------------------

function rich_blocks(array $lines, string $mode, string $context): string
{
    $html  = '';
    $para  = [];
    $list  = null; // ['type' => 'ul'|'ol', 'items' => []]
    $count = count($lines);

    $flush = static function () use (&$html, &$para, &$list, $mode, $context): void {
        if ($para !== []) {
            $html .= rich_tag('p', $mode, $context) . implode('<br>', array_map(static fn($l) => rich_inline($l, $mode, $context), $para)) . '</p>';
            $para = [];
        }
        if ($list !== null) {
            $html .= rich_tag($list['type'], $mode, $context);
            foreach ($list['items'] as $item) {
                $html .= rich_tag('li', $mode, $context) . rich_inline($item, $mode, $context) . '</li>';
            }
            $html .= '</' . $list['type'] . '>';
            $list = null;
        }
    };

    for ($i = 0; $i < $count; $i++) {
        $t = trim($lines[$i]);

        if ($t === '') {
            $flush();
            continue;
        }

        // [[section style]] ... [[/section]], which may contain anything else.
        if (preg_match('/^\[\[section(?:\s+([a-z]+))?\]\]$/', $t, $m)) {
            $flush();
            $style = in_array($m[1] ?? '', RICH_SECTION_STYLES, true) ? $m[1] : 'peach';
            $inner = [];
            $depth = 1;
            for ($i++; $i < $count; $i++) {
                $u = trim($lines[$i]);
                if (preg_match('/^\[\[section\b/', $u)) {
                    $depth++;
                } elseif ($u === '[[/section]]' && --$depth === 0) {
                    break;
                }
                $inner[] = $lines[$i];
            }
            $html .= rich_section(rich_blocks($inner, $mode, $style), $style, $mode);
            continue;
        }
        if ($t === '[[/section]]') {
            continue;
        }
        if ($t === '[[more]]') {
            $flush();
            $html .= $mode === 'web' ? '<span id="read-more" class="rich-more" aria-hidden="true"></span>' : '';
            continue;
        }
        if (preg_match('/^\[\[image\s+([^\]|]+?)\s*(?:\|\s*(.*?))?\]\]$/', $t, $m)) {
            $flush();
            $html .= rich_image(trim($m[1]), trim((string) ($m[2] ?? '')), $mode);
            continue;
        }
        if (preg_match('/^\[\[button\s+(.+?)\s*\|\s*(\S+?)\s*\]\]$/', $t, $m)) {
            $flush();
            $html .= rich_button($m[1], $m[2], $mode, $context);
            continue;
        }
        if (preg_match('/^(#{2,3})\s+(.+)$/', $t, $m)) {
            $flush();
            $tag   = strlen($m[1]) === 2 ? 'h2' : 'h3';
            $html .= rich_tag($tag, $mode, $context) . rich_inline($m[2], $mode, $context) . '</' . $tag . '>';
            continue;
        }
        if (str_starts_with($t, '>')) {
            $flush();
            $quote  = [];
            $author = '';
            for (; $i < $count && str_starts_with(trim($lines[$i]), '>'); $i++) {
                $q = trim(substr(trim($lines[$i]), 1));
                if (preg_match('/^(?:—|–|--|-)\s*(.+)$/u', $q, $who)) {
                    $author = $who[1];
                } elseif ($q !== '') {
                    $quote[] = $q;
                }
            }
            $i--;
            $html .= rich_quote($quote, $author, $mode, $context);
            continue;
        }
        $bullet   = preg_match('/^(?:[-*•])\s+(.+)$/u', $t, $bulletMatch) === 1;
        $numbered = !$bullet && preg_match('/^\d+[.)]\s+(.+)$/', $t, $numberMatch) === 1;
        if ($bullet || $numbered) {
            $type = $numbered ? 'ol' : 'ul';
            if ($para !== [] || ($list !== null && $list['type'] !== $type)) {
                $flush();
            }
            $list ??= ['type' => $type, 'items' => []];
            $list['items'][] = $numbered ? $numberMatch[1] : $bulletMatch[1];
            continue;
        }

        if ($list !== null) {
            $flush();
        }
        $para[] = $t;
    }
    $flush();
    return $html;
}

// ---------------------------------------------------------------------------
// Inline marks: links, bold, italic
// ---------------------------------------------------------------------------

function rich_inline(string $text, string $mode, string $context = ''): string
{
    $links = [];
    $keep  = static function (string $label, string $target) use (&$links, $mode, $context): string {
        $href = rich_href($target, $mode);
        if ($href === null) {
            return $label;
        }
        $links[] = [$label, $href];
        return "\x01" . (count($links) - 1) . "\x02";
    };

    // [text](address), then bare https:// addresses.
    $text = preg_replace_callback('/\[([^\]\n]+)\]\(([^)\s]+)\)/', static fn($m) => $keep($m[1], $m[2]), $text);
    $text = preg_replace_callback('#\bhttps?://[^\s<>"\x01\x02]+[^\s<>"\x01\x02.,;:!?)\]\'"]#', static fn($m) => $keep($m[0], $m[0]), $text);

    $html = rich_marks(e($text));

    return preg_replace_callback('/\x01(\d+)\x02/', static function ($m) use ($links, $mode, $context): string {
        [$label, $href] = $links[(int) $m[1]];
        $style = $mode === 'email' ? ' style="color:' . (in_array($context, ['green', 'gold'], true) ? '#F6E2D4' : '#7A6230') . ';text-decoration:underline"' : '';
        return '<a href="' . e($href) . '"' . $style . '>' . rich_marks(e($label)) . '</a>';
    }, $html);
}

/** **bold** and _italic_ on already-escaped text. */
function rich_marks(string $html): string
{
    $html = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/', '<strong>$1</strong>', $html);
    return preg_replace('/(?<![\w&#])_(?=\S)(.+?)(?<=\S)_(?![\w])/', '<em>$1</em>', $html);
}

/** A safe address for a link, or null. Site pages become full addresses in email. */
function rich_href(string $target, string $mode): ?string
{
    $target = trim(html_entity_decode($target, ENT_QUOTES, 'UTF-8'));
    if ($target === '' || !link_is_safe($target)) {
        return null;
    }
    if (preg_match('/^(https?:|mailto:|tel:)/i', $target) || str_starts_with($target, '#')) {
        return $target;
    }
    if ($mode === 'email') {
        require_once INCLUDES_PATH . '/mail.php';
        return absolute_url($target);
    }
    return url($target);
}

// ---------------------------------------------------------------------------
// Pieces
// ---------------------------------------------------------------------------

/** Opening tag, with inline styles for email. */
function rich_tag(string $tag, string $mode, string $context): string
{
    if ($mode === 'web') {
        return '<' . $tag . '>';
    }
    $colour = in_array($context, ['green', 'gold'], true) ? '#FFFFFF' : '#3A3A3A';
    $head   = in_array($context, ['green', 'gold'], true) ? '#FFFFFF' : '#243A39';
    $style  = match ($tag) {
        'p'      => "margin:0 0 16px;font-family:'Open Sans',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.7;color:$colour",
        'h2'     => "margin:24px 0 12px;font-family:Georgia,'Times New Roman',serif;font-size:22px;line-height:1.3;font-weight:normal;color:$head",
        'h3'     => "margin:20px 0 10px;font-family:Arial,Helvetica,sans-serif;font-size:17px;line-height:1.4;color:$head",
        'ul', 'ol' => 'margin:0 0 16px;padding-left:22px',
        'li'     => "margin:0 0 6px;font-family:'Open Sans',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:$colour",
        default  => '',
    };
    return '<' . $tag . ($style !== '' ? ' style="' . $style . '"' : '') . '>';
}

function rich_section(string $inner, string $style, string $mode): string
{
    if ($mode === 'web') {
        return '<div class="rich-section rich-section--' . e($style) . '">' . $inner . '</div>';
    }
    [$background, $border] = match ($style) {
        'green' => ['#243A39', '#243A39'],
        'gold'  => ['#9D7F3C', '#9D7F3C'],
        'white' => ['#FFFFFF', '#E8DCD1'],
        default => ['#F6E2D4', '#F6E2D4'],
    };
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border-collapse:collapse">'
         . '<tr><td style="padding:24px 26px 10px;background:' . $background . ';border:1px solid ' . $border . '">' . $inner . '</td></tr></table>';
}

function rich_quote(array $lines, string $author, string $mode, string $context): string
{
    if ($lines === []) {
        return '';
    }
    $text = implode('<br>', array_map(static fn($l) => rich_inline($l, $mode, $context), $lines));
    if ($mode === 'web') {
        return '<figure class="rich-quote"><blockquote><p>' . $text . '</p></blockquote>'
             . ($author !== '' ? '<figcaption>' . e($author) . '</figcaption>' : '') . '</figure>';
    }
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;border-collapse:collapse">'
         . '<tr><td style="padding:6px 0 6px 22px;border-left:3px solid #CDA02D">'
         . '<p style="margin:0 0 8px;font-family:Georgia,\'Times New Roman\',serif;font-size:19px;line-height:1.5;font-style:italic;color:#243A39">' . $text . '</p>'
         . ($author !== '' ? '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#7A6230">— ' . e($author) . '</p>' : '')
         . '</td></tr></table>';
}

function rich_button(string $label, string $target, string $mode, string $context): string
{
    $href = rich_href($target, $mode);
    if ($href === null) {
        return '';
    }
    if ($mode === 'web') {
        return '<p class="rich-button"><a class="btn btn--primary" href="' . e($href) . '">' . e($label) . '</a></p>';
    }
    [$background, $colour] = in_array($context, ['green', 'gold'], true) ? ['#F6E2D4', '#243A39'] : ['#243A39', '#FFFFFF'];
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px"><tr>'
         . '<td style="background:' . $background . '"><a href="' . e($href) . '" style="display:inline-block;padding:13px 26px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;letter-spacing:0.12em;text-transform:uppercase;color:' . $colour . ';text-decoration:none">'
         . e($label) . '</a></td></tr></table>';
}

/** An image from the Media Library. Paths not in the library are left out. */
function rich_image(string $path, string $caption, string $mode): string
{
    static $library = [];
    if (!array_key_exists($path, $library)) {
        $library[$path] = null;
        if (link_is_safe($path) && str_starts_with($path, 'uploads/')) {
            try {
                $library[$path] = db_row('SELECT FilePath, AltText, Caption FROM MediaLibrary WHERE FilePath = ?', [$path]);
            } catch (Throwable $error) {
                $library[$path] = null;
            }
        }
    }
    $row = $library[$path];
    if ($row === null) {
        return '';
    }
    $alt     = (string) ($row['AltText'] ?? '');
    $caption = $caption !== '' ? $caption : (string) ($row['Caption'] ?? '');

    if ($mode === 'web') {
        return '<figure class="rich-figure"><img src="' . e(image_url($path, 'web')) . '" alt="' . e($alt) . '" loading="lazy">'
             . ($caption !== '' ? '<figcaption>' . e($caption) . '</figcaption>' : '') . '</figure>';
    }
    require_once INCLUDES_PATH . '/mail.php';
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0 20px;border-collapse:collapse"><tr><td>'
         . '<img src="' . e(mail_origin() . image_url($path, 'web')) . '" alt="' . e($alt) . '" width="536" style="display:block;width:100%;max-width:536px;height:auto;border:0">'
         . ($caption !== '' ? '<p style="margin:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#7A6230">' . e($caption) . '</p>' : '')
         . '</td></tr></table>';
}
