<?php namespace Common\Core\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailTemplatePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->hasPermission('mail_templates.view');
    }

    public function show(User $user)
    {
        return $user->hasPermission('mail_templates.view');
    }

    public function update(User $user)
    {
        return $user->hasPermission('mail_templates.update');
    }
}
