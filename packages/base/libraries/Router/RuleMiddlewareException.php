<?php

namespace packages\base\Router;

use packages\base\Exception;

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
