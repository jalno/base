<?php

namespace packages\base\HTTP;

use packages\base\Exception;

class ResponseException extends Exception
{
    private $response;
    private $request;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
class ServerException extends ResponseException
{
}
class ClientException extends ResponseException
{
}
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
