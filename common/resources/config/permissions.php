<?php

return [
    'roles' => [
        'users' => [
            'users.view'  => 1,
            'localizations.view' => 1,
            'pages.view' => 1,
            'files.create' => 1,
            'plans.view' => 1,
        ],

        'guests' => [
            'users.view' => 1,
            'pages.view' => 1,
        ],
    ],
    'all' => [
        'admin' => [
            [
                'name' => 'admin.access',
                'description' => 'Required in order to access any admin area page.',
            ],
            [
                'name' => 'permissions.view',
                'description' => 'Allows viewing of permissions list.'
            ],
            [
                'name' => 'appearance.update',
                'description' => 'Allows access to appearance editor.'
            ]
        ],

        'roles' => [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ],

        'analytics' => [
            [
                'name' => 'reports.view',
                'description' => 'Allows access to analytics page.',
            ]
        ],

        'pages' => [
            'pages.view',
            'pages.create',
            'pages.update',
            'pages.delete',
        ],

        'files' => [
            'files.view',
            'files.create',
            'files.delete',
        ],

        'users' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ],

        'localizations' => [
            'localizations.view',
            'localizations.create',
            'localizations.update',
            'localizations.delete',
        ],

        'mail_templates' => [
            'mail_templates.view',
            'mail_templates.update',
        ],

        'settings' => [
            'settings.view',
            'settings.update',
        ],

        'billing_plans' => [
            'plans.view',
            'plans.create',
            'plans.update',
            'plans.delete',
        ],
    ]
];
