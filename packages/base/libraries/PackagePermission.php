<?php

namespace packages\base;

class PackagePermission extends PackageConfigException
{
    private $permission;

    public function __construct($package, $permission, $message = '')
    {
        $this->package = $package;
        $this->permission = $permission;
        parent::__construct($message);
    }

    public function getPermission()
    {
        return $this->permission;
    }
}
