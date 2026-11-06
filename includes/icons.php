<?php
/**
 * Inline SVG icons (stroke-based, inherit currentColor).
 * Icons are decorative by default (aria-hidden); pair them with visible text.
 */

declare(strict_types=1);

function icon(string $name, string $class = 'icon'): string
{
    static $paths = [
        'leaf'    => '<path d="M5 21c0-9 6-15 16-16-1 10-7 16-16 16Z"/><path d="M5 21 14 12"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.4A4.6 4.6 0 0 0 21 9.8C21 6 17 3 12 3Z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7" r="1"/>',
        'camera'  => '<path d="M4 7h3l2-3h6l2 3h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13" r="4"/>',
        'mic'     => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M8 21h8"/>',
        'group'   => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c2.8.4 5 2.8 5 5.8"/>',
        'chat'    => '<path d="M4 5h16v11H9l-5 4V5Z"/><path d="M8 10h8M8 13h5"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'flask'   => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-9V3"/><path d="M7.5 15h9"/>',
        'brush'   => '<path d="M18 3l3 3-9 9-3-3 9-9Z"/><path d="M9 12c-3 0-5 2-5 5 0 1.5-1 2.5-2 3 4 1 9 0 9-5"/>',
        'sprout'  => '<path d="M12 21v-9"/><path d="M12 12C12 7 8 5 4 5c0 5 3 7 8 7Z"/><path d="M12 14c0-4 3-6 8-6 0 4-3 6-8 6Z"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 6 9-6"/>',
        'person'  => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.5 18.5c1.3-2 3.3-3 5.5-3s4.2 1 5.5 3"/>',
        'eye'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'shield'  => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'check'   => '<path d="m5 12 5 5 9-10"/>',
        'pen'     => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/>',
    ];

    $svg = $paths[$name] ?? '';

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $svg . '</svg>';
}

/**
 * Decorative botanical branch used in hero sections.
 */
function botanical(string $class = 'botanical'): string
{
    $leaves = '';
    for ($i = 0, $y = 380; $y > 40; $y -= 42, $i++) {
        $x = 100 + ($i % 2 === 0 ? -2 : 2);
        $leaves .= sprintf('<path d="M%1$d %2$d q -46 -6 -70 -46 q 44 2 70 46Z"/>', $x, $y);
        $leaves .= sprintf('<path d="M%1$d %2$d q 46 -6 70 -46 q -44 2 -70 46Z"/>', $x, $y - 20);
    }

    return '<svg class="' . e($class) . '" viewBox="0 0 200 420" fill="none" stroke="currentColor" stroke-width="1.4" '
         . 'stroke-linejoin="round" aria-hidden="true" focusable="false">'
         . '<path d="M100 420 C 96 300 104 160 100 10"/>' . $leaves . '</svg>';
}
