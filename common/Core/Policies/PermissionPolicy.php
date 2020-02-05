<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('permissions.view');
    }
}