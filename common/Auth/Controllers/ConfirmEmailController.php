<?php namespace Common\Auth\Controllers;

use App\User;
use Auth;
use Illuminate\Http\Request;
use Common\Core\Controller;

class ConfirmEmailController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var User
     */
    private $user;

    /**
     * ChangePasswordController constructor.
     *
     * @param Request $request
     * @param User $user
     */
    public function __construct(Request $request, User $user)
    {
        $this->user = $user;
        $this->request = $request;
    }

    /**
     * Confirm account by specified confirmation code.
     *
     * @param string $code
     * @return mixed
     */
    public function confirm($code = null)
    {
        if ( ! $code) return redirect('/');

        $user = $this->user->where('confirmation_code', $code)->firstOrFail();

        $user->confirmed = 1;
        $user->confirmation_code = null;
        $user->save();

        Auth::login($user);

        return redirect('/');
    }
}