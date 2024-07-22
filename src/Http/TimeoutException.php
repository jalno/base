<?php

namespace packages\base\Http;

class TimeoutException extends \Exception
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
