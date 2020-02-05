<?php

return [
    'roles' => [
        'users' => [
            'links.view' => 1,
            'links.create' => 1,
            'users.view'  => 1,
            'localizations.view' => 1,
            'pages.view' => 1,
            'files.create' => 1,
            'plans.view',
        ],
        'guests' => [
            'links.view' => 1,
            'users.view'  => 1,
            'pages.view' => 1,
        ]
    ],
    'all' => [
        'links' => [
            'links.view',
            'links.create',
            'links.update',
            'links.delete',
        ],
    ]
];
