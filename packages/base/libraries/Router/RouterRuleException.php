<?php

namespace packages\base\Router;

use packages\base\Exception;

class RouterRuleException extends Exception
{
    /** @var \packages\base\router\Rule */
    private $rule;

    public function __construct(Rule $rule, string $message = '')
    {
        parent::__construct($message);
        $this->rule = $rule;
    }

    /**
     * Getter for rule.
     */
    public function getRule(): Rule
    {
        return $this->rule;
    }
}