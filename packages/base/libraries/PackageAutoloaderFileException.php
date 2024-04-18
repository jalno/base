<?php

namespace packages\base;

class PackageAutoloaderFileException extends PackageConfigException
{
    private $autoloaderfile;

    public function __construct($package, $file)
    {
        $this->package = $package;
        $this->autoloaderfile = $file;
    }
}
