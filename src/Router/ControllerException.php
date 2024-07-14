<?php

namespace packages\base\Router;

class ControllerException extends RouterRuleException
{

    public function __construct(public readonly string $controller)
    {
    }

    /**
     * Getter for controller.
     */
    public function getController(): string
    {
        return $this->controller;
    }
}
