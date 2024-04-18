<?php

namespace packages\base;

class PackageConfigException extends Exception
{
    /** @var string */
    protected $package;

    /**
     * @param string package name
     * @param string $message the Exception message to throw
     */
    public function __construct(string $package, string $message = '')
    {
        $this->package = $package;
        parent::__construct($message);
    }

    /**
     * Getter for package name.
     */
    public function getPackage(): string
    {
        return $this->package;
    }
}
