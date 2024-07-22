<?php

namespace packages\base\Router;

use packages\base\Exception;

class RouterRuleException extends Exception
{
    public function __construct(private Rule $rule, string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Getter for rule.
     */
    public function getRule(): Rule
    {
        return $this->rule;
    }
}
