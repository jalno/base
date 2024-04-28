<?php

namespace packages\base\Router;

use packages\base\Exception;

class ControllerException extends RouterRuleException
{
    /** @var string */
    private $controller;

    public function __construct(string $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Getter for controller.
     */
    public function getController(): string
    {
        return $this->controller;
    }
}