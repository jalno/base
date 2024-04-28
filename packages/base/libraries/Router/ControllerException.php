<?php

namespace packages\base\Router;

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
