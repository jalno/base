<?php

namespace packages\base\Frontend;

class SourceViewParentException extends \Exception
{
    private $view;
    private $source;

    public function __construct($view, $source)
    {
        $this->view = $view;
        $this->source = $source;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getSource()
    {
        return $this->source;
    }
}
