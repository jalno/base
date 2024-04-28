<?php

namespace packages\base\Router;

use packages\base\Exception;

class RulePartNameException extends RouterRulePart
{
    public function __construct($part)
    {
        parent::__construct($part, 'name is not assigned');
    }
}