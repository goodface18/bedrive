<?php namespace Common\Auth\Controllers;

use Storage;
use App\User;
use Illuminate\Http\Request;
use Common\Core\Controller;

class UserAvatarController extends Controller {

    /**
     * Laravel request instance.
     *
     * @var Request
     */
    private $request;

    /**
     * User instance.
     *
     * @var User
     */
    private $user;

    /**
     * Storage service instance with disk set to public.
     *
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    private $storage;

    /**
     * UserAvatarController constructor.
     *
     * @param Request $request
     * @param User $user
     */
    public function __construct(Request $request, User $user)
    {
        $this->request = $request;
        $this->storage = Storage::disk('public');
        $this->user = $user;
    }

    /**
     * Store avatar on disk and attach it to specified user.
     *
     * @param int $userId
     * @return User
     */
    public function store($userId) {

        $user = $this->user->findOrFail($userId);

        $this->authorize('update', $user);

        $this->validate($this->request, [
            'avatar' => 'required|image|max:1500',
        ]);

        //delete old user avatar
        $this->storage->delete($user->getOriginal('avatar'));

        //store new avatar on public disk
        $path = $this->request->file('avatar')->storePublicly('avatars', ['disk' => 'public']);

        //attach avatar to user model
        $user->fill(['avatar' => $path])->save();

        return $user;
    }

    /**
     * Delete specified user's avatar and detach it from user model.
     *
     * @param int $userId
     * @return User
     */
    public function destroy($userId)
    {
        $user = $this->user->findOrFail($userId);

        $this->authorize('update', $user);

        $this->storage->delete($user->getOriginal('avatar'));

        $user->fill(['avatar' => null])->save();

        return $user;
    }
}
