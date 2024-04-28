<?php

namespace packages\base\Router;

class InvalidRegexException extends RouterRuleException
{
    protected $regex;

    public function __construct(string $regex, Rule $rule)
    {
        parent::__construct($rule, 'regex is invalid');
        $this->regex = $regex;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }
}
