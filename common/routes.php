<?php

Route::group(['prefix' => 'secure', 'middleware' => 'web'], function () {
    //BOOTSTRAP
    Route::get('bootstrap-data', 'Common\Core\Controllers\BootstrapController@getBootstrapData');

    //AUTH ROUTES
    Route::post('auth/register', 'Common\Auth\Controllers\RegisterController@register');
    Route::post('auth/login', 'Common\Auth\Controllers\LoginController@login');
    Route::post('auth/logout', 'Common\Auth\Controllers\LoginController@logout');
    Route::post('auth/password/email', 'Common\Auth\Controllers\ForgotPasswordController@sendResetLinkEmail');
    Route::post('auth/password/reset', 'Common\Auth\Controllers\ResetPasswordController@reset')->name('password.reset');
    Route::get('auth/email/confirm/{code}', 'Common\Auth\Controllers\ConfirmEmailController@confirm');

    //SOCIAL AUTHENTICATION
    Route::get('auth/social/{provider}/connect', 'Common\Auth\Controllers\SocialAuthController@connect');
    Route::get('auth/social/{provider}/login', 'Common\Auth\Controllers\SocialAuthController@login');
    Route::get('auth/social/{provider}/callback', 'Common\Auth\Controllers\SocialAuthController@loginCallback');
    Route::post('auth/social/extra-credentials', 'Common\Auth\Controllers\SocialAuthController@extraCredentials');
    Route::post('auth/social/{provider}/disconnect', 'Common\Auth\Controllers\SocialAuthController@disconnect');

    //USERS
    Route::get('users', 'Common\Auth\Controllers\UserController@index');
    Route::get('users/{id}', 'Common\Auth\Controllers\UserController@show');
    Route::post('users', 'Common\Auth\Controllers\UserController@store');
    Route::put('users/{id}', 'Common\Auth\Controllers\UserController@update');
    Route::delete('users/delete-multiple', 'Common\Auth\Controllers\UserController@deleteMultiple');

    //ROLES
    Route::get('roles', 'Common\Auth\Roles\RolesController@index');
    Route::post('roles', 'Common\Auth\Roles\RolesController@store');
    Route::put('roles/{id}', 'Common\Auth\Roles\RolesController@update');
    Route::delete('roles/{id}', 'Common\Auth\Roles\RolesController@destroy');
    Route::post('roles/{id}/add-users', 'Common\Auth\Roles\RolesController@addUsers');
    Route::post('roles/{id}/remove-users', 'Common\Auth\Roles\RolesController@removeUsers');

    //USER PASSWORD
    Route::post('users/{id}/password/change', 'Common\Auth\Controllers\ChangePasswordController@change');

    //USER AVATAR
    Route::post('users/{id}/avatar', 'Common\Auth\Controllers\UserAvatarController@store');
    Route::delete('users/{id}/avatar', 'Common\Auth\Controllers\UserAvatarController@destroy');

    //USER ROLES
    Route::post('users/{id}/roles/attach', 'Common\Auth\Roles\UserRolesController@attach');
    Route::post('users/{id}/roles/detach', 'Common\Auth\Roles\UserRolesController@detach');

    //USER PERMISSIONS
    Route::post('users/{id}/permissions/add', 'Common\Auth\UserPermissionsController@add');
    Route::post('users/{id}/permissions/remove', 'Common\Auth\UserPermissionsController@remove');

    //UPLOADS
    Route::get('uploads/server-max-file-size', 'Common\Files\Controllers\ServerMaxUploadSizeController@index');
	Route::get('uploads', 'Common\Files\Controllers\FileEntriesController@index');
	Route::get('uploads/download', 'Common\Files\Controllers\DownloadFileController@download');
	Route::post('uploads/images', 'Common\Files\Controllers\PublicUploadsController@images');
	Route::post('uploads/videos', 'Common\Files\Controllers\PublicUploadsController@videos');
	Route::get('uploads/{id}', 'Common\Files\Controllers\FileEntriesController@show');
	Route::post('uploads', 'Common\Files\Controllers\FileEntriesController@store');
	Route::put('uploads/{id}', 'Common\Files\Controllers\FileEntriesController@update');
    Route::delete('uploads', 'Common\Files\Controllers\FileEntriesController@destroy');
    Route::post('uploads/{id}/add-preview-token', 'Common\Files\Controllers\AddPreviewTokenController@store');

    //PAGES
    Route::get('pages', 'Common\Pages\PagesController@index');
    Route::get('pages/{id}', 'Common\Pages\PagesController@show');
    Route::post('pages', 'Common\Pages\PagesController@store');
    Route::put('pages/{id}', 'Common\Pages\PagesController@update');
    Route::delete('pages', 'Common\Pages\PagesController@destroy');

    //VALUE LISTS
    Route::get('value-lists/{names}', 'Common\Core\Controllers\ValueListsController@get');

    //SETTINGS
    Route::get('settings', 'Common\Settings\SettingsController@index');
    Route::post('settings', 'Common\Settings\SettingsController@persist');

    //APPEARANCE EDITOR
    Route::post('admin/appearance', 'Common\Admin\Appearance\Controllers\AppearanceController@save');
    Route::get('admin/appearance/values', 'Common\Admin\Appearance\Controllers\AppearanceController@getValues');
    Route::get('admin/icons', 'Common\Admin\Appearance\Controllers\IconController@index');

    //LOCALIZATIONS
    Route::get('localizations', 'Common\Localizations\LocalizationsController@index');
    Route::post('localizations', 'Common\Localizations\LocalizationsController@store');
    Route::put('localizations/{id}', 'Common\Localizations\LocalizationsController@update');
    Route::delete('localizations/{id}', 'Common\Localizations\LocalizationsController@destroy');
    Route::get('localizations/{name}', 'Common\Localizations\LocalizationsController@show');

    //MAIL TEMPLATES
    Route::get('mail-templates', 'Common\Mail\MailTemplatesController@index');
    Route::post('mail-templates/render', 'Common\Mail\MailTemplatesController@render');
    Route::post('mail-templates/{id}/restore-default', 'Common\Mail\MailTemplatesController@restoreDefault');
    Route::put('mail-templates/{id}', 'Common\Mail\MailTemplatesController@update');

    //OTHER ADMIN ROUTES
    Route::get('admin/analytics/stats', 'Common\Admin\Analytics\AnalyticsController@stats');
    Route::post('artisan/call', 'Common\Admin\Console\ArtisanController@call');

    //billing plans
    Route::get('billing/plans', 'Common\Billing\Plans\BillingPlansController@index');
    Route::post('billing/plans', 'Common\Billing\Plans\BillingPlansController@store');
    Route::post('billing/plans/sync', 'Common\Billing\Plans\BillingPlansController@sync');
    Route::put('billing/plans/{id}', 'Common\Billing\Plans\BillingPlansController@update');
    Route::delete('billing/plans', 'Common\Billing\Plans\BillingPlansController@destroy');

    //subs
    Route::get('billing/subscriptions', 'Common\Billing\Subscriptions\SubscriptionsController@index');
    Route::post('billing/subscriptions', 'Common\Billing\Subscriptions\SubscriptionsController@store');
    Route::post('billing/subscriptions/stripe', 'Common\Billing\Gateways\Stripe\StripeController@createSubscription');
    Route::post('billing/subscriptions/paypal/agreement/create', 'Common\Billing\Gateways\Paypal\PaypalController@createSubscriptionAgreement');
    Route::post('billing/subscriptions/paypal/agreement/execute', 'Common\Billing\Gateways\Paypal\PaypalController@executeSubscriptionAgreement');
    Route::delete('billing/subscriptions/{id}', 'Common\Billing\Subscriptions\SubscriptionsController@cancel');
    Route::put('billing/subscriptions/{id}', 'Common\Billing\Subscriptions\SubscriptionsController@update');
    Route::post('billing/subscriptions/{id}/resume', 'Common\Billing\Subscriptions\SubscriptionsController@resume');
    Route::post('billing/subscriptions/{id}/change-plan', 'Common\Billing\Subscriptions\SubscriptionsController@changePlan');
    Route::post('billing/stripe/cards/add', 'Common\Billing\Gateways\Stripe\StripeController@addCard');

    //contact us page
    Route::post('contact-page', 'Common\Pages\ContactPageController@sendMessage');
    Route::post('recaptcha/verify', 'Common\Validation\RecaptchaController@verify');
});

//paypal
Route::get('billing/paypal/callback/approved', 'Common\Billing\Gateways\Paypal\PaypalController@approvedCallback');
Route::get('billing/paypal/callback/canceled', 'Common\Billing\Gateways\Paypal\PaypalController@canceledCallback');

Route::get('billing/paypal/loading', 'Common\Billing\Gateways\Paypal\PaypalController@loadingPopup');

//stripe webhook
Route::post('billing/stripe/webhook', 'Common\Billing\Webhooks\StripeWebhookController@handleWebhook');
Route::post('billing/paypal/webhook', 'Common\Billing\Gateways\Paypal\PaypalWebhookController@handleWebhook');