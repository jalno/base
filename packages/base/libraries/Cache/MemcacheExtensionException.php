<?php

namespace packages\base\Cache;

use packages\base\Exception;

class MemcacheExtensionException extends Exception
{
    public function __construct()
    {
        parent::__construct("memcached extenstion doesn't loaded");
    }
}
