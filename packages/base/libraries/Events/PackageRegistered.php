<?php

namespace packages\base\Events;

use packages\base\Event;
use packages\base\Package;

class PackageRegistered extends Event
{
    private $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }
}
