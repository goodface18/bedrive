<?php

return [
    //dates
    ['name' => 'dates.format', 'value' => 'yyyy-MM-dd'],
    ['name' => 'dates.locale', 'value' => 'en_US'],

    //social login
    ['name' => 'social.google.enable', 'value' => 1],
    ['name' => 'social.twitter.enable', 'value' => 1],
    ['name' => 'social.facebook.enable', 'value' => 1],

    //real time
    ['name' => 'realtime.enable', 'value' => 0],

    //temp
    ['name' => 'registration.disable', 'value' => 0],

    //cache
    ['name' => 'cache.report_minutes', 'value' => 60],

    //branding
    ['name' => 'branding.use_custom_theme', 'value' => 1],
    ['name' => 'branding.favicon', 'value' => 'favicon.ico'],

    //logos
    ['name' => 'branding.logo_dark', 'value' => 'client/assets/images/logo-dark.png'],
    ['name' => 'branding.logo_light', 'value' => 'client/assets/images/logo-light.png'],

    //translations
    ['name' => 'i18n.default_localization', 'value' => 'english'],
    ['name' => 'i18n.enable', 'value' => 1],

    //sentry
    ['name' => 'logging.sentry_public', 'value' => null],

    //pusher
    ['name' => 'realtime.pusher_key', 'value' => null],

    //menus
    ['name' => 'menus', 'value' => json_encode([])],

    //homepage
    ['name' => 'homepage.type', 'value' => 'default'],

    //billing
    ['name' => 'billing.enable', 'value' => false],
    ['name' => 'billing.paypal_test_mode', 'value' => true],
    ['name' => 'billing.stripe_test_mode', 'value' => true],
    ['name' => 'billing.stripe.enable', 'value' => false],
    ['name' => 'billing.paypal.enable', 'value' => false],
    ['name' => 'billing.accepted_cards', 'value' => json_encode(['visa', 'mastercard', 'american-express', 'discover'])],
];
