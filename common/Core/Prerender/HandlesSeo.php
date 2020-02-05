<?php

namespace Common\Core\Prerender;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;

trait HandlesSeo
{
    /**
     * @param $data
     * @param $options
     * @return Response|null
     */
    protected function handleSeo(&$data = null, $options = null)
    {
        list($resource, $verb) = $this->parseRouteAction();

        $namespace = Arr::get($options, 'prerender.config', "$resource.$verb");

        if ($metaTags = config("seo.$namespace")) {
            $data['seo'] = (new MetaTags($metaTags, $data, $namespace));
        }

        if (defined('SHOULD_PRERENDER')) {
            $viewName = Arr::get($options, 'prerender.view', "$resource.$verb");
            return response(view("prerender.$viewName")->with('meta', $data['seo']));
        }
    }

    protected function parseRouteAction()
    {
        // 'App/Http/Controllers/ArtistController@show'
        $uses = request()->route()->action['uses'];

        // get resource name and verb from route action
        preg_match('/\\\(\w+?)Controller@(\w+)$/', $uses, $matches);
        return [
            kebab_case($matches[1]), // resource
            kebab_case($matches[2]), // verb
        ];
    }
}