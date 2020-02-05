<?php

namespace App;

use Common\Auth\BaseUser;
use Common\Files\UserFileEntry;

class User extends BaseUser
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function folders()
    {
        return $this->belongsToMany(Folder::class, 'user_file_entry', 'user_id', 'file_entry_id')
            ->using(UserFileEntry::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class, 'user_file_entry', 'user_id', 'file_entry_id')
            ->using(UserFileEntry::class);
    }

    /**
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entries($options = ['owner' => true])
    {
        return $this->belongsToMany(FileEntry::class, 'user_file_entry', 'user_id', 'file_entry_id')
            ->using(UserFileEntry::class);
    }
}
