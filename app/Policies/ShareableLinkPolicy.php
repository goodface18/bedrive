<?php

namespace App\Policies;

use Hash;
use App\User;
use App\FileEntry;
use App\ShareableLink;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShareableLinkPolicy
{
    use HandlesAuthorization;

    public function show(User $user, ShareableLink $link)
    {
        return $user->hasPermission('links.view') || $link->user_id === $user->id;
    }

    public function create(User $user)
    {
        return $user->hasPermission('links.create');
    }

    public function update(User $user, ShareableLink $link)
    {
        return $user->hasPermission('links.update') || $link->user_id === $user->id;
    }

    public function destroy(User $user, ShareableLink $link)
    {
        return $user->hasPermission('links.delete') || $link->user_id === $user->id;
    }
}
