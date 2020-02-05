<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('roles.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('roles.update');
    }

    public function destroy(User $user)
    {
        return $user->hasPermission('roles.delete');
    }
}
