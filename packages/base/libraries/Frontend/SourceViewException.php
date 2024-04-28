<?php

namespace packages\base\Frontend;

class SourceViewException extends \Exception
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