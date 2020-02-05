<?php

return [
    // admin
    ['method' => 'POST', 'name' => 'settings'],
    ['method' => 'POST', 'name' => 'admin/appearance'],
    ['method' => 'PUT', 'name' => 'mail-templates/{id}'],
    ['method' => 'POST', 'name' => 'cache/clear'],
    ['method' => 'POST', 'name' => 'artisan/call'],

    // localizations
    ['method' => 'DELETE', 'name' => 'localizations/{id}'],
    ['method' => 'PUT', 'name' => 'localizations/{id}'],
    ['method' => 'POST', 'name' => 'localizations'],

    // pages
    ['method' => 'DELETE', 'name' => 'pages'],

    // billing plans
    ['method' => 'POST', 'name' => 'billing/plans'],
    ['method' => 'POST', 'name' => 'billing/plans/sync'],
    ['method' => 'PUT', 'name' => 'billing/plans/{id}'],
    ['method' => 'DELETE', 'name' => 'billing/plans'],

    // subscriptions
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'billing/subscriptions'],
    ['method' => 'PUT', 'origin' => 'admin', 'name' => 'billing/subscriptions/{id}'],
    ['method' => 'DELETE', 'origin' => 'admin', 'name' => 'billing/subscriptions/{id}'],

    // users
    ['method' => 'POST', 'name' => 'users/{id}/password/change'],
    ['method' => 'DELETE', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'PUT', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}/roles/attach'],
    ['method' => 'POST', 'origin' => 'admin', 'name' => 'users/{id}/roles/detach'],
    ['method' => 'DELETE', 'name' => 'users/delete-multiple'],

    // roles
    ['method' => 'DELETE', 'name' => 'roles/{id}'],
    ['method' => 'PUT', 'name' => 'roles/{id}'],
    ['method' => 'POST', 'name' => 'roles'],
    ['method' => 'POST', 'name' => 'roles/{id}/add-users'],
    ['method' => 'POST', 'name' => 'roles/{id}/remove-users'],

    // contact
    ['method' => 'POST', 'name' => 'contact-page'],

    // uploads
    ['method' => 'DELETE', 'name' => 'uploads', 'origin' => 'admin'],
];
