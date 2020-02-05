<?php namespace Common\Auth;

trait FormatsPermissions {

    /**
     * Encode permissions into json string.
     *
     * @param array|string $value
     */
    public function setPermissionsAttribute($value)
    {
        if ( ! is_array($value)) {
            $value = json_decode($value);
        }

        $permissions = array_map(function($permissionValue) {
            return $permissionValue ? 1 : 0;
        }, $value);

        $this->attributes['permissions'] = json_encode($permissions);
    }

    /**
     * Return decoded permissions.
     *
     * @param string $value
     * @return array
     */
    public function getPermissionsAttribute($value)
    {
        $roles = json_decode($value, true) ?: [];

        $roles = array_map(function($permissionValue) {
            return $permissionValue ? 1 : 0;
        }, $roles);

        return $roles;
    }
}