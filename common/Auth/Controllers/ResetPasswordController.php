<?php namespace Common\Auth\Controllers;

use App\User;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * @var User
     */
    private $user;

    /**
     * @var Request
     */
    private $request;

    /**
     * Create a new controller instance.
     *
     * @param User $user
     * @param Request $request
     */
    public function __construct(User $user, Request $request)
    {
        $this->middleware('guest');

        $this->user = $user;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResetResponse()
    {
        return $this->success(['data' =>
            $this->user->with('roles')->where('email', $this->request->get('email'))->first()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return $this->error(['email' => trans($response)]);
    }
}