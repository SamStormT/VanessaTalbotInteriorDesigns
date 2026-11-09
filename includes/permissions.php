<?php
/**
 * Role-based permission map.
 *
 * Each module lists every action that exists, then what Admin and Editor may
 * do. SuperAdmin may do every listed action. Anything not listed is refused
 * for everyone, so a mistyped permission fails closed.
 */

declare(strict_types=1);

function permission_map(): array
{
    $publishable = ['view', 'create', 'edit', 'publish', 'archive', 'delete'];

    // Content that Editors can prepare but only Admins can publish.
    $content = ['actions' => $publishable, 'Admin' => $publishable, 'Editor' => ['view', 'create', 'edit']];

    return [
        'dashboard'    => ['actions' => ['view'], 'Admin' => ['view'], 'Editor' => ['view']],
        'settings'     => ['actions' => ['view', 'edit'], 'Admin' => ['view', 'edit'], 'Editor' => []],
        'homepage'     => ['actions' => ['view', 'edit'], 'Admin' => ['view', 'edit'], 'Editor' => ['view', 'edit']],
        'about'        => ['actions' => ['view', 'edit'], 'Admin' => ['view', 'edit'], 'Editor' => ['view', 'edit']],
        'services'     => ['actions' => $publishable, 'Admin' => ['view', 'create', 'edit', 'publish', 'archive'], 'Editor' => ['view', 'edit']],
        'about_items'  => $content,
        'page_sections' => $content,
        'portfolio'    => $content,
        'speaking'     => $content,
        'testimonials' => $content,
        'blogs'        => $content,
        'products'     => ['actions' => $publishable, 'Admin' => ['view', 'create', 'edit', 'publish', 'archive'], 'Editor' => ['view', 'edit']],
        'media'        => ['actions' => ['view', 'create', 'edit', 'delete'], 'Admin' => ['view', 'create', 'edit', 'delete'], 'Editor' => ['view', 'create', 'edit']],
        'orders'       => ['actions' => ['view', 'edit', 'export'], 'Admin' => ['view', 'edit', 'export'], 'Editor' => []],
        'subscribers'  => ['actions' => ['view', 'create', 'edit', 'archive', 'delete', 'export'], 'Admin' => ['view', 'create', 'edit', 'archive'], 'Editor' => []],
        'messages'     => ['actions' => ['view', 'edit', 'archive', 'delete'], 'Admin' => ['view', 'edit', 'archive'], 'Editor' => []],
        'users'        => ['actions' => ['view', 'create', 'edit', 'archive'], 'Admin' => [], 'Editor' => []],
        'activity'     => ['actions' => ['view'], 'Admin' => [], 'Editor' => []],
    ];
}

/** Can this role perform "module.action"? */
function role_can(string $role, string $permission): bool
{
    [$module, $action] = array_pad(explode('.', $permission, 2), 2, '');
    $rules = permission_map()[$module] ?? null;

    if ($rules === null || !in_array($action, $rules['actions'], true)) {
        return false;
    }

    return $role === 'SuperAdmin' || in_array($action, $rules[$role] ?? [], true);
}
