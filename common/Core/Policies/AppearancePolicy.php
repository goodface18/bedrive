<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppearancePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('appearance.index');
    }

    public function update(User $user)
    {
        return $user->hasPermission('appearance.update');
    }
}
