<?php namespace Common\Files;

use App\User;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Common\Files\Traits\HandlesEntryPaths;
use Common\Files\Traits\HashesId;
use Common\Tags\HandlesTags;
use Common\Tags\Tag;
use Storage;

/**
 * FileEntry
 *
 * @mixin \Eloquent
 * @property integer $id
 * @property integer $parent_id
 * @property string $name
 * @property string $file_name
 * @property string $file_size
 * @property string $mime
 * @property string $extension
 * @property boolean $thumbnail
 * @property string $preview_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $type
 * @property-read FileEntry|null $parent
 * @property-read Collection $users
 * @property-read Collection $owner
 * @property string $path
 * @property string $public_path
 * @method static \Illuminate\Database\Query\Builder|FileEntry onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|FileEntry rootOnly()
 * @method static \Illuminate\Database\Query\Builder|FileEntry onlyStarred()
 * @method static \Illuminate\Database\Query\Builder|FileEntry whereRootOrParentNotTrashed()
 */
class FileEntry extends Model
{
    use SoftDeletes, HashesId, HandlesEntryPaths, HandlesTags;

    protected $guarded = ['id'];
    protected $hidden  = ['pivot', 'preview_token'];
    protected $appends = ['hash', 'url'];
    protected $casts = [
        'id' => 'integer',
        'file_size' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'thumbnail' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(FileEntryUser::class, 'user_file_entry', 'file_entry_id', 'user_id')
            ->using(UserFileEntry::class)
            ->select('first_name', 'last_name', 'email', 'users.id', 'avatar')
            ->withPivot('owner', 'permissions')
            ->withTimestamps()
            ->orderBy('user_file_entry.created_at', 'asc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(static::class, 'parent_id')->withoutGlobalScope('fsType');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->wherePivot('user_id', Auth::user()->id);
    }

    /**
     * Get url for previewing upload.
     *
     * @param string $value
     * @return string
     */
    public function getUrlAttribute($value)
    {
        if ($value) return $value;
        if ( ! isset($this->attributes['type']) || $this->attributes['type'] === 'folder') {
            return null;
        }

        if (Arr::get($this->attributes, 'public')) {
            return "storage/$this->public_path/$this->file_name";
        } else {
            return 'secure/uploads/'.$this->attributes['id'];
        }
    }

    public function getStoragePath()
    {
        if ($this->public) {
            return "$this->public_path/$this->file_name";
        } else {
            return "$this->file_name/$this->file_name";
        }
    }

    public function getDisk()
    {
        if ($this->public) {
            return Storage::drive('public');
        } else {
            return Storage::drive(config('common.site.uploads_disk'));
        }
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeWhereRootOrParentNotTrashed(Builder $query)
    {
        return $query->whereNull('parent_id')
            ->orWhereHas('parent', function(Builder $query) {
                return $query->whereNull('deleted_at');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function owner()
    {
        return $this->users()->wherePivot('owner', true);
    }

    /**
     * @return User
     */
    public function getOwner() {
        return $this->owner->first();
    }

    /**
     * Select all entries user has access to.
     *
     * @param Builder $builder
     * @param $userId
     * @param bool|null $owner
     * @return Builder
     */
    public function scopeWhereUser(Builder $builder, $userId, $owner = null) {
        return $builder->whereIn('id', function ($query) use($userId, $owner) {
            $query->select('file_entry_id')
                ->from('user_file_entry')
                ->where('user_id', $userId);

            // if $owner is not null, need to load either only
            // entries user owns or entries user does not own
            //if $owner is null, load all entries
            if ( ! is_null($owner)) {
                $query->where('owner', $owner);
            }
        });
    }

    /**
     * Select only entries that were created by specified user.
     *
     * @param Builder $builder
     * @param $userId
     * @return Builder
     */
    public function scopeWhereOwner(Builder $builder, $userId) {
        return $this->scopeWhereUser($builder, $userId, true);
    }

    /**
     * Select only entries that were not created by specified user.
     *
     * @param Builder $builder
     * @param $userId
     * @return Builder
     */
    public function scopeWhereNotOwner(Builder $builder, $userId) {
        return $this->scopeWhereUser($builder, $userId, false);
    }

    /**
     * Get path of specified entry.
     *
     * @param int $id
     * @return string
     */
    public function findPath($id)
    {
        $entry = $this->find($id, ['path']);
        return $entry ? $entry->getOriginal('path') : '';
    }

    /**
     * Return file entry name with extension.
     * @return string
     */
    public function getNameWithExtension() {
        if ( ! $this->exists) return '';

        $extension = pathinfo($this->name, PATHINFO_EXTENSION);

        if ( ! $extension && $this->extension) {
            return $this->name .'.'. $this->extension;
        }

        return $this->name;
    }
}
