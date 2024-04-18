<?php

namespace packages\base\IO\Directory;

class TMP extends Local
{
    public function __construct()
    {
        $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM123456789';
        $this->directory = sys_get_temp_dir();
        do {
            $this->basename = substr(str_shuffle($chars), 0, rand(5, 10));
        } while ($this->exists());
        $this->make(false);
    }

    public function __destruct()
    {
        $this->delete();
    }
}
