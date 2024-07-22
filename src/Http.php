<?php

namespace packages\base;

use Illuminate\Http\Request;

/**
 * @property static array $client
 */
class Http
{

    public static $client = [];
    public static $server = [];
    public static $request = [];
    public static $data = [];
    public static $files = [];

    public static function set(Request $request): void
    {
        self::$server['ip'] = $request->server->get("SERVER_ADDR");
        self::$server['port'] = $request->getPort();
        self::$server['webserver'] = $request->server->get("SERVER_SOFTWARE");
        self::$server['hostname'] = $request->server->get("SERVER_NAME");
        self::$client['ip'] = $request->ip();
        self::$client['port'] = $request->server->get("REMOTE_PORT");
        self::$client['agent'] = $request->userAgent();
        self::$request['query'] = $request->getQueryString();
        self::$request['method'] = $request->getMethod();
        self::$request['uri'] = $request->path();
        self::$request['microtime'] = $request->server->get('REQUEST_TIME_FLOAT');
        self::$request['time'] = $request->server->get('REQUEST_TIME');
        self::$request['hostname'] = $request->getHttpHost();
        self::$request['scheme'] = $request->getScheme();
        self::$request['referer'] = $request->server->get('HTTP_REFERER');

        self::$request['ajax'] = ($request->query->get('ajax') == 1);
        self::$request['post'] = $request->request->all();
        self::$request['get'] = $request->query->all();
        self::$request['cookies'] = $request->cookies->all();
        // TODO: 
        // self::$files = self::makeFilesStandard($_FILES);
        self::$data = $request->input();
    }

    public static function getURL(): string
    {
        return request()->getUri();
    }

    public static function getData(string $name): mixed
    {
        return request()->input($name);
    }

    public static function getFormData(string $name): mixed
    {

        return request()->request->get($name);
    }

    public static function getDataForm(string $name): mixed
    {
        return self::getFormData($name);
    }

    public static function getURIData(string $name): mixed
    {
        return request()->query->get($name);
    }

    public static function is_post(): bool
    {
        return request()->isMethod("POST");
    }

    public static function getHeader(string $name): mixed
    {
        return request()->headers->get($name);
    }

    // public static function setcookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    // public static function removeCookie(string $name)
    // public static function redirect($url)
    // public static function pid()
    // public static function setHttpCode($code)
    // public static function setHeader($name, $value = null)
    // public static function setMimeType($type, $charset = null)
    // public static function setLength($length)
    // public static function tojson($charset = 'utf-8')
    // public static function is_safe_referer(string $referer = '')

}
