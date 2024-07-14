<?php

namespace packages\base;

class PackageNotConfiged extends Exception
{
    private $package;

    public function __construct($package)
    {
        $this->package = $package;
        parent::__construct("package {$package} not configured");
    }

    public function getPackage()
    {
        return $this->package;
    }
}
