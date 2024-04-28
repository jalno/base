<?php

namespace packages\base\Frontend;

class SourceConfigException extends \Exception
{
    private $source;

    public function __construct($message, $source)
    {
        parent::__construct($message);
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }
}
