<?php

namespace packages\base\cache;

use packages\base\Exception;

class MemcacheExtensionException extends Exception
{
    public function __construct()
    {
        parent::__construct("memcached extenstion doesn't loaded");
    }
}
