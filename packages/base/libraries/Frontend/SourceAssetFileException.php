<?php

namespace packages\base\Frontend;

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
