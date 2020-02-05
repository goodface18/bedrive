<?php

return [
    //branding
    ['name' => 'branding.site_name', 'value' => 'BeDrive'],

    //cache
    ['name' => 'cache.report_minutes', 'value' => 60],

    //other
    ['name' => 'site.force_https', 'value' => 0],

    //menus
    ['name' => 'menus', 'value' => json_encode([
        ['name' => 'Drive Sidebar', 'position' => 'drive-sidebar', 'items' => [
            ['type' => 'route', 'order' => 1, 'label' => 'Shared with me', 'action' => 'drive/shares', 'icon' => 'people'],
            ['type' => 'route', 'order' => 2, 'label' => 'Recent', 'action' => 'drive/recent', 'icon' => 'access-time'],
            ['type' => 'route', 'order' => 3, 'label' => 'Starred', 'action' => 'drive/starred', 'icon' => 'star'],
            ['type' => 'route', 'order' => 4, 'label' => 'Trash', 'action' => 'drive/trash', 'icon' => 'delete']
        ]],
    ])],

    //uploads
    ['name' => 'uploads.max_size', 'value' => 52428800],
    ['name' => 'uploads.available_space', 'value' => 104857600],
    ['name' => 'uploads.blocked_extensions', 'value' => json_encode(['exe', 'application/x-msdownload', 'x-dosexec'])],

    //landing page
    ['name' => 'landingPage.background', 'value' => 'client/assets/images/homepage-bg.jpg'],
    ['name' => 'landingPage.title', 'value' => 'BeDrive. A new home for your files.'],
    ['name' => 'landingPage.subtitle', 'value' => 'Register or Login now to upload, backup, manage and access your files on any device, from anywhere, free.'],
    ['name' => 'landingPage.ctaButton', 'value' => 'Register Now'],
];
