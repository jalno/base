<?php

namespace packages\base\Router;

class PathException extends RouterRuleException
{
    public function __construct(Rule $rule, private mixed $path, string $message)
    {
        parent::__construct($rule, $message);
    }

    /**
     * Getter for wrong path.
     */
    public function getPath()
    {
        return $this->path;
    }
}
