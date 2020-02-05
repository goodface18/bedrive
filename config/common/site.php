<?php

return [
    'version' => env('APP_VERSION'),
    'demo'    => env('IS_DEMO_SITE', false),
    'disable_update_auth' => env('DISABLE_UPDATE_AUTH', false),
    'use_symlinks' => env('USE_SYMLINKS', false),
];