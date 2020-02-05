<?php namespace Common\Admin\Appearance\Controllers;

use File;
use Common\Core\Controller;

class IconController extends Controller
{
    public function index()
    {
        $this->authorize('index', 'AppearancePolicy');

        $iconFile = File::get(public_path('client/assets/icons/merged.svg'));

        preg_match_all('/id="([a-z09-]+?)"/m', $iconFile, $matches);

        return $this->success(['icons' => $matches[1]]);
    }
}
