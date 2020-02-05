<?php namespace Common\Auth;

use App\User;
use Common\Auth\Events\UserCreated;
use Common\Auth\Roles\Role;
use Common\Settings\Settings;
use Common\Files\Actions\Deletion\PermanentlyDeleteEntries;

class UserRepository {

    /**
     * User model instance.
     *
     * @var User
     */
    protected $user;

    /**
     * Role model instance.
     *
     * @var Role
     */
    protected $role;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * UserRepository constructor.
     *
     * @param User $user
     * @param Role $role
     * @param Settings $settings
     */
    public function __construct(
        User $user,
        Role $role,
        Settings $settings
    )
    {
        $this->user  = $user;
        $this->role = $role;
        $this->settings = $settings;
    }

    /**
     * Find user with given id or throw an error.
     *
     * @param integer $id
     * @param array $lazyLoad
     * @return User
     */
    public function findOrFail($id, $lazyLoad = [])
    {
        return $this->user->with($lazyLoad)->findOrFail($id);
    }

    /**
     * Paginate all users using given params.
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateUsers($params)
    {
        $orderBy    = isset($params['order_by']) ? $params['order_by'] : 'created_at';
        $orderDir   = isset($params['order_dir']) ? $params['order_dir'] : 'desc';
        $perPage    = isset($params['per_page']) ? $params['per_page'] : 13;
        $searchTerm = isset($params['query']) ? $params['query'] : null;
        $roleId    = isset($params['role_id']) ? (int) $params['role_id'] : null;
        $roleName  = isset($params['role_name']) ? $params['role_name'] : null;

        $query = $this->user->with('roles');

        if ($searchTerm) {
            $query->where('email', 'LIKE', "%$searchTerm%");
        }

        if ($roleId) {
            $query->whereHas('roles', function($q) use($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        if ($roleName) {
            $query->whereHas('roles', function($q) use($roleName) {
                $q->where('roles.name', $roleName);
            });
        }

        return $query->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    /**
     * Return first user matching attributes or create a new one.
     *
     * @param array $params
     * @return User
     */
    public function firstOrCreate($params)
    {
        $user = $this->user->where('email', $params['email'])->first();

        if (is_null($user)) {
            $user = $this->create($params);
        }

        return $user;
    }

    /**
     * Create a new user and assign default customer role to it.
     *
     * @throws \Exception
     *
     * @param array $params
     * @return User
     */
    public function create($params)
    {
        /** @var User $user */
        $user = $this->user->forceCreate($this->formatParams($params));

        try {
            if ( ! isset($params['roles']) || ! $this->attachRoles($user, $params['roles'])) {
                $this->assignDefaultRole($user);
            }
        } catch (\Exception $e) {
            //delete user if there were any errors creating/assigning
            //purchase codes or roles, so there are no artifacts left
            $user->delete();
            throw($e);
        }

        event(new UserCreated($user));

        return $user;
    }

    /**
     * Update given user.
     *
     * @param User $user
     * @param array $params
     *
     * @return User
     */
    public function update(User $user, $params)
    {
        $user->forceFill($this->formatParams($params, 'update'))->save();

        if (isset($params['roles'])) {
            $this->attachRoles($user, $params['roles']);
        }

        return $user->load('roles');
    }

    /**
     * Delete multiple users.
     *
     * @param array $ids
     * @return array
     */
    public function deleteMultiple($ids)
    {
        foreach ($ids as $id) {
            $user = $this->user->find($id);
            if (is_null($user)) continue;

            $user->social_profiles()->delete();
            $user->roles()->detach();

            if ($user->subscribed()) {
                $user->subscriptions->each->cancelAndDelete();
            }

            $user->delete();

            $entryIds = $user->entries(['owner' => true])->pluck('file_entries.id');
            app(PermanentlyDeleteEntries::class)->execute($entryIds);
        }

        return $ids;
    }

    /**
     * Prepare given params for inserting into database.
     *
     * @param array $params
     * @param string $type
     * @return array
     */
    protected function formatParams($params, $type = 'create')
    {
        $formatted = [
            'avatar'      => isset($params['avatar']) ? $params['avatar'] : null,
            'first_name'  => isset($params['first_name']) ? $params['first_name'] : null,
            'last_name'   => isset($params['last_name']) ? $params['last_name'] : null,
            'language'    => isset($params['language']) ? $params['language'] : config('app.locale'),
            'country'     => isset($params['country']) ? $params['country'] : null,
            'timezone'    => isset($params['timezone']) ? $params['timezone'] : null,
            'confirmed'   => isset($params['confirmed']) ? $params['confirmed'] : 1,
            'confirmation_code' => isset($params['confirmation_code']) ? $params['confirmation_code'] : null,
        ];

        if (array_key_exists('available_space', $params)) {
            $formatted['available_space'] = is_null($params['available_space']) ? null : (int) $params['available_space'];
        }

        //cast permission values to integer
        if (isset($params['permissions'])) {
            $formatted['permissions'] = array_map(function($value) {
                return (int) $value;
            }, $params['permissions']);
        }

        if ($type === 'create') {
            $formatted['email'] = $params['email'];
            $formatted['password'] = isset($params['password']) ? bcrypt($params['password']) : null;
        }

        return $formatted;
    }

    /**
     * Assign roles to user, if any are given.
     *
     * @param User  $user
     * @param array $roles
     * @type string $type
     *
     * @return int
     */
    public function attachRoles(User $user, $roles, $type = 'sync')
    {
        if (empty($roles)) return 0;
        $roleIds = $this->role->whereIn('id', $roles)->get()->pluck('id');
        return $user->roles()->$type($roleIds);
    }

    /**
     * Detach specified roles from user.
     *
     * @param User $user
     * @param int[] $roles
     *
     * @return int
     */
    public function detachRoles(User $user, $roles)
    {
        return $user->roles()->detach($roles);
    }

    /**
     * Add specified permissions to user.
     *
     * @param User $user
     * @param array $permissions
     * @return User
     */
    public function addPermissions(User $user, $permissions)
    {
        $existing = $user->permissions;

        foreach ($permissions as $permission) {
            $existing[$permission] = 1;
        }

        $user->forceFill(['permissions' => $existing])->save();

        return $user;
    }

    /**
     * Remove specified permissions from user.
     *
     * @param User $user
     * @param array $permissions
     * @return User
     */
    public function removePermissions(User $user, $permissions)
    {
        $existing = $user->permissions;

        foreach ($permissions as $permission) {
            unset($existing[$permission]);
        }

        $user->forceFill(['permissions' => $existing])->save();

        return $user;
    }

    /**
     * Assign default role to given user.
     *
     * @param User $user
     */
    protected function assignDefaultRole(User $user)
    {
        $defaultRole = $this->role->getDefaultRole();

        if ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        }
    }
}