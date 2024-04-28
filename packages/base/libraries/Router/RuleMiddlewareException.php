<?php

namespace packages\base\Router;

class RuleMiddlewareException extends \Exception
{
    private $middleware;

    public function __construct($middleware)
    {
        $this->middleware = $middleware;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }
}
