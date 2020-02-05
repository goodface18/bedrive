<?php

namespace Common\Settings\Validators\MailCredentials;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MailCredentialsMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->markdown('common::emails.mail-validation')
            ->subject(config('app.name') . ' Mail Set Up Successfully!');
    }
}
