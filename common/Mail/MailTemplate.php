<?php

namespace Common\Mail;

use Illuminate\Database\Eloquent\Model;

/**
 * Common\MailTemplate
 *
 * @property int $id
 * @property string $file_name
 * @property string $display_name
 * @property string $subject
 * @property string $action
 * @property bool $base
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin \Eloquent
 */
class MailTemplate extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
