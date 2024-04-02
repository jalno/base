<?php

namespace packages\base\IO\file;

class tmp extends local
{
    public function __construct()
    {
        $this->directory = sys_get_temp_dir();
        $this->basename = basename(tempnam($this->directory, ''));
    }

    public function __destruct()
    {
        $this->delete();
    }
}
