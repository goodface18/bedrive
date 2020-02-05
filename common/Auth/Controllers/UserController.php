<?php namespace Common\Auth\Controllers;

use App\User;
use Auth;
use Common\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Common\Auth\UserRepository;
use Common\Core\Controller;
use Common\Auth\Requests\ModifyUsers;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserController extends Controller {

    /**
     * @var User
     */
    private $user;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param User $user
     * @param UserRepository $userRepository
     * @param Request $request
     * @param Settings $settings
     */
    public function __construct(User $user, UserRepository $userRepository, Request $request, Settings $settings)
    {
        $this->user = $user;
        $this->request = $request;
        $this->userRepository = $userRepository;

        $this->middleware('auth', ['except' => ['show']]);
        $this->settings = $settings;
    }

    /**
     * Return a collection of all registered users.
     *
     * @return LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize('index', User::class);

        return $this->userRepository->paginateUsers($this->request->all());
    }

    /**
     * @param integer $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $relations = array_filter(explode(',', $this->request->get('with', '')));
        $relations = array_merge(['roles', 'social_profiles'], $relations);

        if ($this->settings->get('envato.enable')) {
            $relations[] = 'purchase_codes';
        }

        $user = $this->user->with($relations)->findOrFail($id);

        $this->authorize('show', $user);

        return $this->success(['user' => $user]);
    }

    /**
     * Create a new user.
     *
     * @param ModifyUsers $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ModifyUsers $request)
    {
        $this->authorize('store', User::class);

        $user = $this->userRepository->create($this->request->all());

        return $this->success(['user' => $user], 201);
    }

    /**
     * Update an existing user.
     *
     * @param integer $id
     * @param ModifyUsers $request
     *
     * @return JsonResponse
     */
    public function update($id, ModifyUsers $request)
    {
        $user = $this->userRepository->findOrFail($id);

        $this->authorize('update', $user);

        $user = $this->userRepository->update($user, $this->request->all());

        return $this->success(['user' => $user]);
    }

    /**
     * Delete multiple users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMultiple()
    {
        $this->authorize('destroy', User::class);

        $this->validate($this->request, [
            'ids' => 'required|array|min:1'
        ]);

        $users = $this->user->whereIn('id', $this->request->get('ids'))->get();

        // guard against current user or admin user deletion
        foreach ($users as $user) {
            if ($user->id === Auth::id()) {
                return $this->error(['general' => "Could not delete currently logged in user: {$user->email}"]);
            }

            if ($user->is_admin) {
                return $this->error(['general' => "Could not delete admin user: {$user->email}"]);
            }
        }

        $this->userRepository->deleteMultiple($this->request->get('ids'));

        return $this->success([], 204);
    }
}
