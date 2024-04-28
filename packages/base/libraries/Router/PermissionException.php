<?php

namespace packages\base\Router;

use packages\base\Exception;

class PermissionException extends Exception
{
    private $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
        parent::__construct('permission is unknown');
    }

    public function getPermission()
    {
        return $this->permission;
    }
}

