<?php

/**
 * Config Filament Shield.
 *
 * Fichier : config/filament-shield.php
 *
 * Après installation de Shield (composer require bezhansalleh/filament-shield),
 * publiez cette config :
 *   php artisan vendor:publish --tag=filament-shield-config
 * puis remplacez le contenu par celui-ci.
 */
return [

    'super_admin' => [
        'enabled' => true,
        'name'    => 'super_admin',
    ],

    'filament_user' => [
        'enabled' => true,
        'name'    => 'panel_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view', 'view_any', 'create', 'update',
            'delete', 'delete_any', 'force_delete',
            'force_delete_any', 'restore', 'restore_any',
            'replicate', 'reorder',
        ],
        'page'   => 'page',
        'widget' => 'widget',
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],

    /*
    |------------------------------------------------------------------
    | Shield Resource (page de gestion des rôles)
    |------------------------------------------------------------------
    | Placement dans le groupe "Administration" du sidebar
    | avec le label "Rôles utilisateurs"
    */
    'shield_resource' => [
        'should_register_navigation' => false,
        'slug'             => 'roles-utilisateurs',
        'navigation_sort'  => 10,
        'navigation_badge' => true,
        'navigation_group' => 'Administration',
        'is_globally_searchable' => false,
        'cluster' => null,
    ],
];
