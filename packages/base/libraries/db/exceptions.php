<?php

namespace packages\base\db;

class InputRequired extends \Exception
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
class duplicateRecord extends \Exception
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
class InputDataType extends \Exception
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
