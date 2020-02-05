<?php

namespace Common\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Common\Settings\Settings;

class RedirectToHttps
{
    /**
     * @var Settings
     */
    private $settings;

    /**
     * RedirectToHttps constructor.
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Request $request, Closure $next)
    {
        if ( ! $request->secure() && $this->settings->get('site.force_https')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}