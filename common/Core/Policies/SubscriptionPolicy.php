<?php

namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('subscription.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('subscription.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('subscription.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('subscription.update');
    }

    public function destroy(User $user)
    {
        return $user->hasPermission('subscription.delete');
    }
}
