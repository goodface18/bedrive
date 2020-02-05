<?php namespace Common\Validation;

use Common\Core\Controller;
use Common\Settings\Settings;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class RecaptchaController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Client
     */
    private $http;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @param Request $request
     * @param Client $http
     * @param Settings $settings
     */
    public function __construct(Request $request, Client $http, Settings $settings)
    {
        $this->request = $request;
        $this->http = $http;
        $this->settings = $settings;
    }

    public function verify()
    {
        $this->validate($this->request, [
            'token' => 'required|string'
        ]);

        $response = $this->http->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'response' => $this->request->get('token'),
                'secret' => $this->settings->get('recaptcha.secret_key'),
                'remoteip' => $this->request->getClientIp()
            ]
        ]);

        $response = json_decode($response->getBody()->getContents(), true);

        $success = $response['success'] && $response['score'] > 0.0;

        return $this->success(['success' => $success]);

    }
}
