<?php

namespace packages\base\IO;

class RawListenSocketException extends SocketException
{
    public function __construct()
    {
        parent::__construct('cannot listen on RAW Sockets');
    }
}
