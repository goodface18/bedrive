<?php

namespace Common\Settings\Validators;

interface SettingsValidator
{
    /**
     * @param array $settings
     * @return null|array
     */
    public function fails($settings);
}