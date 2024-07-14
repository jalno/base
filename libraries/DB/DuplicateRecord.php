<?php

namespace packages\base\DB;

class DuplicateRecord extends \Exception
{
    private $input;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function getInput()
    {
        return $this->input;
    }
}
