<?php

namespace Common\Tags;

use Common\Files\FileEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Tag extends Model
{
    protected $hidden = ['pivot'];
    protected $casts = ['id' => 'integer'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function files()
    {
        return $this->morphedByMany(FileEntry::class, 'taggable');
    }

    /**
     * @param array $ids
     * @param null|int $userId
     */
    public function attachEntries($ids, $userId = null)
    {
        if ($userId) {
            $ids = collect($ids)->mapWithKeys(function($id) use($userId) {
                return [$id => ['user_id' => $userId]];
            });
        }

        $this->files()->syncWithoutDetaching($ids);
    }

    /**
     * @param array $ids
     * @param null|int $userId
     */
    public function detachEntries($ids, $userId = null)
    {
        $query = $this->files();

        if ($userId) {
            $query->wherePivot('user_id', $userId);
        }

        $query->detach($ids);
    }

    /**
     * @param Collection $tags
     * @return Collection|Tag[]
     */
    public function insertOrRetrieve(Collection $tags)
    {
        $tags = $tags->toLower('name');
        $existing = $this->getByNames($tags->pluck('name'), $tags->first()['type']);

        $new = $tags->filter(function($tag) use($existing) {
            return !$existing->contains('name', strtolower($tag['name']));
        });

        if ($new->isNotEmpty()) {
            $this->insert($new->toArray());
            return $this->getByNames($tags->pluck('name'), $tags->first()['type']);
        } else {
            return $existing;
        }
    }

    /**
     * @param Collection $names
     * @param string $type
     * @return Collection
     */
    public function getByNames(Collection $names, $type = null)
    {
        $query = $this->whereIn('name', $names);
        if ($type) $query->where('type', $type);
        return $query->get()->toLower('name');
    }
}
