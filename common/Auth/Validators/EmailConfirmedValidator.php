<?php namespace Common\Auth\Validators;

use App;
use App\User;
use Common\Settings\Settings;

class EmailConfirmedValidator {

    /**
     * Check if user with specified email has confirmed his email address.
     *
     * @param string $attribute
     * @param string $value
     * @param array $parameters
     * @return bool
     */
    public function validate($attribute, $value, $parameters) {
        $settings = App::make(Settings::class);

        //don't need to validate email, bail
        if ( ! $settings->get('require_email_confirmation')) return true;

        //if email address is not taken yet, bail
        if ( ! $user = User::where('email', $value)->first()) return true;

        //check if specified email is confirmed
        return (bool) $user->confirmed;
    }
}