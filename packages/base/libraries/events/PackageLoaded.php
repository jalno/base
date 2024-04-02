<?php

namespace packages\base\events;

use packages\base\event;
use packages\base\package;

class PackageLoaded extends event
{
    private $package;

    public function __construct(package $package)
    {
        $this->package = $package;
    }

    public function getPackage(): package
    {
        return $this->package;
    }
}
