<?php

namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BillingPlanPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('plans.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('plans.view');
    }

    public function store(User $user)
    {
        return $user->hasPermission('plans.create');
    }

    public function update(User $user)
    {
        return $user->hasPermission('plans.update');
    }

    public function destroy(User $user)
    {
        return $user->hasPermission('plans.delete');
    }
}
