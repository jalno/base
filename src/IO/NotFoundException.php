<?php

namespace packages\base\IO;

class NotFoundException extends Exception
{
    public function __construct($file, ?string $message = null)
    {
        if ($message === null) {
            $message = "Cannot find this resource";
            if ($file) {
                $message .= ": " . $file->getPath();
            }
        }
        parent::__construct($file, $message);
    }
}
