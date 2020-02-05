<?php namespace Common\Auth\Controllers;

use Log;
use Auth;
use Exception;
use Common\Auth\Oauth;
use Illuminate\Http\Request;
use Common\Settings\Settings;
use Common\Core\Controller;

class SocialAuthController extends Controller
{
    /**
     * @var Oauth
     */
    private $oauth;

    /**
     * Laravel request instance.
     */
    private $request;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Request $request
     * @param Oauth $oauth
     * @param Settings $settings
     */
    public function __construct(Request $request, Oauth $oauth, Settings $settings)
    {
        $this->oauth = $oauth;
        $this->request = $request;
        $this->settings = $settings;

        $this->middleware('auth', ['only' => ['connect', 'disconnect']]);
        $this->middleware('guest', ['only' => ['login']]);

        //abort if registration should be disabled
        if ($this->settings->get('disable.registration')) abort(404);
    }

    /**
     * Connect specified social account to currently logged in user.
     *
     * @param string $provider
     * @return mixed
     */
    public function connect($provider)
    {
        return $this->oauth->connect($provider);
    }

    /**
     * Disconnect specified social account from currently logged in user.
     *
     * @param string $provider
     * @return mixed
     */
    public function disconnect($provider)
    {
        return $this->oauth->disconnect($provider);
    }

    /**
     * Login with specified social provider.
     *
     * @param  string $provider
     * @return mixed
     */
    public function login($provider)
    {
        return $this->oauth->loginWith($provider);
    }

    /**
     * Handle callback from one of the social auth services.
     *
     * @param  string $provider
     * @return mixed
     */
    public function loginCallback($provider)
    {
        $externalProfile = null;

        try {
            $externalProfile = $this->oauth->socializeWith($provider);
        } catch (Exception $e) {
            Log::error($e);
        }

        if ( ! $externalProfile) {
            return $this->oauth->getErrorResponse();
        }

        $existingProfile = $this->oauth->getExistingProfile($externalProfile);
        
        //if user is already logged in, attach returned social account to logged in user
        if (Auth::check()) {
            return $this->oauth->attachProfileToExistingUser(Auth::user(), $externalProfile, $provider);
        }

        //if we have already created a user for this social account, log user in
        if ($existingProfile && $existingProfile->user) {
            return $this->oauth->logUserIn($existingProfile->user);
        }

        //if user is trying to log in with envato and does not have any valid purchases, bail
        if ($provider === 'envato' && empty($externalProfile->purchases)) {
            return $this->oauth->getErrorResponse('You do not have any supported purchases.');
        }

        $credentials = $this->oauth->getCredentialsThatNeedToBeRequested($externalProfile, $provider);

        //we need to request some extra credentials from user before creating account
        if ( ! empty($credentials)) {
            return $this->oauth->requestExtraCredentials($credentials, $provider, $externalProfile);

            //if we have email and didn't create an account for this profile yet, do it now
        } else {
            return $this->oauth->createUserFromOAuthData(['profile' => $externalProfile, 'service' => $provider], true);
        }
    }

    /**
     * Process extra credentials supplied by user
     * that were needed to complete social login.
     * (Password, email, purchase code etc)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function extraCredentials()
    {
        //get data for this social login persisted in session
        $data = $this->oauth->getPersistedData();

        if (!$data) return $this->error();

        //validate user supplied extra credentials
        $errors = $this->oauth->validateExtraCredentials($this->request->all());

        if (!empty($errors)) {
            return $this->error($errors);
        }

        if (!isset($data['profile']->email)) {
            $data['profile']->email = $this->request->get('email');
        }

        return $this->success(['data' => $this->oauth->createUserFromOAuthData($data)]);
    }
}
