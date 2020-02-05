<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocalizationPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('localizations.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('localizations.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('localizations.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('localizations.update');
    }

    public function destroy(User $user)
    {
        return $user->hasPermission('localizations.delete');
    }
}
