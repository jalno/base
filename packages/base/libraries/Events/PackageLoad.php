<?php

namespace packages\base\Events;

use packages\base\Event;

class PackageLoad extends Event
{
    private $package;

    public function __construct(string $package)
    {
        $this->package = $package;
    }

    public function getPackage(): string
    {
        return $this->package;
    }
}
