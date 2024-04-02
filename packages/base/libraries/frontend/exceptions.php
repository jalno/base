<?php

namespace packages\base\frontend;

class SourceAssetFileException extends \Exception
{
    private $assetfile;
    private $source;

    public function __construct($file, $source)
    {
        $this->assetfile = $file;
        $this->source = $source;
    }

    public function getAssetFile()
    {
        return $this->assetfile;
    }

    public function getSource()
    {
        return $this->source;
    }
}
class SourceAssetException extends \Exception
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
class SourceAutoloaderFileException extends \Exception
{
    private $classfile;
    private $source;

    public function __construct($classfile, $source)
    {
        $this->classfile = $classfile;
        $this->source = $source;
    }

    public function getClassFile()
    {
        return $this->classfile;
    }

    public function getSource()
    {
        return $this->source;
    }
}
