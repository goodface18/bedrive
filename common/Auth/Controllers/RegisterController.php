<?php namespace Common\Auth\Controllers;

use Mail;
use App\User;
use Common\Core\BootstrapData;
use Common\Mail\ConfirmEmail;
use Common\Settings\Settings;
use Illuminate\Http\Request;
use Common\Core\Controller;
use Common\Auth\UserRepository;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * @var BootstrapData
     */
    private $bootstrapData;

    /**
     * @param Settings $settings
     * @param UserRepository $repository
     * @param BootstrapData $bootstrapData
     */
    public function __construct(Settings $settings, UserRepository $repository, BootstrapData $bootstrapData)
    {
        $this->settings = $settings;
        $this->repository = $repository;
        $this->bootstrapData = $bootstrapData;

        $this->middleware('guest');

        //abort if registration should be disabled
        if ($this->settings->get('disable.registration')) abort(404);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:5|max:255|confirmed',
        ];

        $this->validate($request, $rules);

        $params = $request->all();
        $needsConfirmation = $this->settings->get('require_email_confirmation');

        if ($needsConfirmation) {
            $code = str_random(30);
            $params['confirmation_code'] = $code;
            $params['confirmed'] = 0;
        }

        $user = $this->create($params);

        if ($needsConfirmation) {
            Mail::queue(new ConfirmEmail($params['email'], $code));
            return $this->success(['type' => 'confirmation_required']);
        }

        $this->guard()->login($user);

        return $this->registered($request, $user);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * The user has been registered.
     *
     * @param Request $request
     * @param $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registered(Request $request, User $user)
    {
        $data = $this->bootstrapData->get();
        return $this->success(['data' => $data]);
    }
}