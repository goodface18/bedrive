@component('mail::message')
# Mail Set Up Successfully!

This email was sent out to test your new mail credentials for {{ config('app.name') }}.
Because you have received this email, mail has been set-up properly and this email can be ignored.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
