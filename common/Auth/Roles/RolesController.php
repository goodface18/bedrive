<?php namespace Common\Auth\Roles;

use App\User;
use Illuminate\Http\Request;
use Common\Core\Controller;

class RolesController extends Controller
{
    /**
     * User model.
     *
     * @var User
     */
    private $user;

    /**
     * Role model.
     *
     * @var Role
     */
    private $role;

    /**
     * Laravel request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * RolesController constructor.
     *
     * @param Request $request
     * @param Role $role
     * @param User $user
     */
    public function __construct(Request $request, Role $role, User $user)
    {
        $this->role   = $role;
        $this->user    = $user;
        $this->request = $request;
    }

    /**
     * Paginate all existing roles.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize('index', Role::class);

        $data = $this->role->paginate(13);

        return $data;
    }

    /**
     * Create a new role.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->authorize('store', Role::class);

        $this->validate($this->request, [
            'name'        => 'required|unique:roles|min:2|max:255',
            'default'     => 'boolean',
            'guests'      => 'boolean',
            'permissions' => 'array'
        ]);

        $role = $this->role->forceCreate([
            'name'        => $this->request->get('name'),
            'permissions' => $this->request->get('permissions'),
            'default'     => $this->request->get('default', 0),
            'guests'      => $this->request->get('guests', 0)
        ]);

        return $this->success(['data' => $role], 201);
    }

    /**
     * Update existing role.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'name'        => "min:2|max:255|unique:roles,name,$id",
            'default'     => 'boolean',
            'guests'      => 'boolean',
            'permissions' => 'array'
        ]);

        $role = $this->role->findOrFail($id);

        $role->fill($this->request->all())->save();

        return $this->success(['data' => $role]);
    }

    /**
     * Delete role matching given id.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('destroy', Role::class);

        $role = $this->role->findOrFail($id);

        $role->users()->detach();
        $role->delete();

        return $this->success([], 204);
    }

    /**
     * Add given users to role.
     *
     * @param integer $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUsers($roleId)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'emails'   => 'required|array|min:1|max:25',
            'emails.*' => 'required|email|max:255'
        ], [
            'emails.*.email'   => 'Email address must be valid.',
            'emails.*.required' => 'Email address is required.',
        ]);

        $role = $this->role->findOrFail($roleId);

        $users = $this->user->with('roles')->whereIn('email', $this->request->get('emails'))->get(['email', 'id']);

        if ($users->isEmpty()) {
            return $this->error([], 422);
        }

        //filter out users that are already attached to this role
        $users = $users->filter(function($user) use($roleId) {
            return ! $user->roles->contains('id', (int) $roleId);
        });

        $role->users()->attach($users->pluck('id')->toArray());

        return $this->success(['data' => $users]);
    }

    /**
     * Remove given users from role.
     *
     * @param integer $roleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeUsers($roleId)
    {
        $this->authorize('update', Role::class);

        $this->validate($this->request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer'
        ]);

        $role = $this->role->findOrFail($roleId);

        $role->users()->detach($this->request->get('ids'));

        return $this->success(['data' => $this->request->get('ids')]);
    }
}
