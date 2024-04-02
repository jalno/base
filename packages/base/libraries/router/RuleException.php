<?php

namespace packages\base\router;

use packages\base\Exception;

class RuleException extends Exception
{
    public function __construct(protected Rule $rule, string $message = '')
    {
        parent::__construct($message);
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }
}
