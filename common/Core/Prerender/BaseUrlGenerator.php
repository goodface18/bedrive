<?php

namespace Common\Core\Prerender;

use Common\Core\Contracts\AppUrlGenerator;

class BaseUrlGenerator implements AppUrlGenerator
{
    const SEPARATOR = '+';

    /**
     * Generate url based on called method name, if there's no specific method.
     *
     * @param string $name
     * @param array $arguments
     * @return string
     */
    public function __call($name, $arguments)
    {
        return url(kebab_case($name));
    }
}