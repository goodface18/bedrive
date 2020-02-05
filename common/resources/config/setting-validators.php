<?php

return [
    Common\Settings\Validators\MailCredentials\MailCredentialsValidator::class,
    Common\Settings\Validators\GoogleLoginValidator::class,
    Common\Settings\Validators\FacebookLoginValidator::class,
    Common\Settings\Validators\TwitterLoginValidator::class,
    Common\Settings\Validators\StorageCredentialsValidator::class,
    Common\Settings\Validators\CacheConfigValidator::class,
    Common\Settings\Validators\AnalyticsCredentialsValidator::class,
    Common\Settings\Validators\QueueCredentialsValidator::class,
    Common\Settings\Validators\LoggingCredentialsValidator::class,
    Common\Settings\Validators\RecaptchaCredentialsValidator::class,
    Common\Settings\Validators\PaypalCredentialsValidator::class,
    Common\Settings\Validators\StripeCredentialsValidator::class,
];