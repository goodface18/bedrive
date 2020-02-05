<?php namespace Common\Database\Seeds;

use DB;
use Carbon\Carbon;
use App\User;
use Common\Auth\Roles\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Collection;

class RolesTableSeeder extends Seeder
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var User
     */
    private $user;

    /**
     * RolesTableSeeder constructor.
     *
     * @param Role $role
     * @param User $user
     */
    public function __construct(Role $role, User $user)
    {
        $this->user = $user;
        $this->role = $role;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->createOrUpdateRole('guests', 'guests');
        $usersRole = $this->createOrUpdateRole('default', 'users');

        $this->attachUsersRoleToExistingUsers($usersRole);
    }

    private function createOrUpdateRole($type, $name)
    {
        $defaultPermissions = config("common.permissions.roles.$name");
        $role = $this->role->firstOrCreate([$type => 1], [$type => 1, 'name' => $name]);
        $role->permissions = array_merge($role->permissions, $defaultPermissions);
        $role->save();
        return $role;
    }

    /**
     * Attach default user's role to all existing users.
     *
     * @param Role $role
     */
    private function attachUsersRoleToExistingUsers(Role $role)
    {
        $this->user->with('roles')->orderBy('id', 'desc')->select('id')->chunk(500, function(Collection $users) use($role) {
            $insert = $users->filter(function(User $user) use ($role) {
                return ! $user->roles->contains('id', $role->id);
            })->map(function(User $user) use($role) {
                return ['user_id' => $user->id, 'role_id' => $role->id, 'created_at' => Carbon::now()];
            })->toArray();

            DB::table('user_role')->insert($insert);
        });
    }
}
