<?php namespace Common\Core\Policies;

use Common\Auth\BaseUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(BaseUser $user)
    {
        return $user->hasPermission('users.view');
    }

    public function show(BaseUser $current, BaseUser $requested)
    {
        return $current->hasPermission('users.view') || $current->id === $requested->id;
    }

    public function store(BaseUser $user)
    {
        return $user->hasPermission('users.create');
    }

    public function update(BaseUser $current, BaseUser $toUpdate = null)
    {
        //user has proper permissions
        if ($current->hasPermission('users.update')) return true;

        //no permissions and not trying to update his own model
        if ( ! $toUpdate || ($current->id !== $toUpdate->id)) return false;

        //user should not be able to change his own permissions or roles
        if ($this->request->get('permissions') || $this->request->get('roles')) {
            return false;
        }

        return true;
    }

    public function destroy(BaseUser $user)
    {
        return $user->hasPermission('users.delete');
    }
}
