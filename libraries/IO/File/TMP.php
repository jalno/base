<?php

namespace packages\base\IO\File;

class TMP extends Local
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
