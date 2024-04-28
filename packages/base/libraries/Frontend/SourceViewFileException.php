<?php

namespace packages\base\Frontend;


class SourceViewFileException extends \Exception
{
    private $viewfile;
    private $source;

    public function __construct($viewfile, $source)
    {
        $this->viewfile = $viewfile;
        $this->source = $source;
    }

    public function getViewFile()
    {
        return $this->viewfile;
    }

    public function getSource()
    {
        return $this->source;
    }
}