<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('pages.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('pages.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('pages.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('pages.update');
    }

    public function destroy(User $user)
    {
        return $user->hasPermission('pages.delete');
    }
}