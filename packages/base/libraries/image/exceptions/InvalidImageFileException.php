<?php

namespace packages\base\Image;

use packages\base\Exception;
use packages\base\IO\File;

class InvalidImageFileException extends Exception
{
    /** @var package\base\IO\File is the invalid image file */
    protected $invalidImageFile;

    public function __construct(File $invalidImageFile, string $message = '')
    {
        $this->invalidImageFile = $invalidImageFile;
        if (!$message) {
            $message = "{$invalidImageFile->getPath()} is an invalid image";
        }
        parent::__construct($message);
    }

    public function getInvalidFile(): File
    {
        return $this->invalidImageFile;
    }
}
