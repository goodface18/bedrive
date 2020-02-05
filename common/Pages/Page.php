<?php namespace Common\Pages;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Page
 *
 * @property int $id
 * @property string $body
 * @property string $slug
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @mixin \Eloquent
 */
class Page extends Model
{
    const DEFAULT_PAGE_TYPE = 'default';

    protected $guarded = ['id'];
}
