<?php

namespace packages\base\Router;

use packages\base\Exception;

class RouterRulePart extends Exception
{
    private $part;

    public function __construct($part, string $message = '')
    {
        $this->part = $part;
        parent::__construct($message);
    }

    /**
     * Getter for wrong part.
     */
    public function getPart()
    {
        return $this->part;
    }
}