<?php

namespace packages\base;

class HTTP
{
    public static $client = [];
    public static $server = [];
    public static $request = [];
    public static $data = [];
    public static $files = [];

    public static function set()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            self::$server['ip'] = $_SERVER['SERVER_ADDR'];
        }
        if (isset($_SERVER['SERVER_PORT'])) {
            self::$server['port'] = $_SERVER['SERVER_PORT'];
        }
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            self::$server['webserver'] = $_SERVER['SERVER_SOFTWARE'];
        }
        if (isset($_SERVER['SERVER_NAME'])) {
            self::$server['hostname'] = $_SERVER['SERVER_NAME'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            self::$client['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['REMOTE_PORT'])) {
            self::$client['port'] = $_SERVER['REMOTE_PORT'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            self::$client['agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_SERVER['QUERY_STRING'])) {
            self::$request['query'] = $_SERVER['QUERY_STRING'];
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            self::$request['method'] = strtolower($_SERVER['REQUEST_METHOD']);
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $temp = explode('?', $_SERVER['REQUEST_URI'], 2);
            self::$request['uri'] = $temp[0];
        }
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            self::$request['microtime'] = $_SERVER['REQUEST_TIME_FLOAT'];
        }
        if (isset($_SERVER['REQUEST_TIME'])) {
            self::$request['time'] = $_SERVER['REQUEST_TIME'];
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            self::$request['hostname'] = $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['REQUEST_SCHEME'])) {
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                self::$request['scheme'] = $_SERVER['HTTP_X_FORWARDED_PROTO'];
            } elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL'])) {
                self::$request['scheme'] = 'on' == $_SERVER['HTTP_X_FORWARDED_SSL'] ? 'https' : 'http';
            } else {
                self::$request['scheme'] = $_SERVER['REQUEST_SCHEME'];
            }
        } elseif (isset($_SERVER['SCRIPT_URI'])) {
            self::$request['scheme'] = parse_url($_SERVER['SCRIPT_URI'], PHP_URL_SCHEME);
        } elseif (isset($_SERVER['HTTPS'])) {
            self::$request['scheme'] = ('on' == $_SERVER['HTTPS']) ? 'https' : 'http';
        } else {
            self::$request['scheme'] = 'http';
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            self::$request['referer'] = $_SERVER['HTTP_REFERER'];
        }
        self::$request['ajax'] = (isset($_GET['ajax']) and 1 == $_GET['ajax']);
        self::$request['post'] = $_POST;
        self::$request['get'] = $_GET;
        self::$files = self::makeFilesStandard($_FILES);
        if (isset($_COOKIE)) {
            self::$request['cookies'] = $_COOKIE;
        }
        self::$data = array_merge($_POST, $_GET);
    }

    public static function getURL(): string
    {
        return self::$request['scheme'].'://'.self::$request['hostname'].urldecode($_SERVER['REQUEST_URI']);
    }

    public static function getData($name)
    {
        if (isset(self::$request['post'][$name])) {
            return self::$request['post'][$name];
        } elseif (isset(self::$request['get'][$name])) {
            return self::$request['get'][$name];
        }

        return null;
    }

    public static function getFormData($name)
    {
        if (isset(self::$request['post'][$name])) {
            return self::$request['post'][$name];
        }

        return null;
    }

    public static function getDataForm($name)
    {
        return self::getFormData($name);
    }

    public static function getURIData($name)
    {
        if (isset(self::$request['get'][$name])) {
            return self::$request['get'][$name];
        }

        return null;
    }

    public static function is_post()
    {
        return 'post' == self::$request['method'];
    }

    public static function setcookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public static function removeCookie(string $name)
    {
        unset(self::$request['cookies']);
        self::setcookie($name, null, 1);
    }

    public static function redirect($url)
    {
        header("Location: {$url}");
    }

    public static function pid()
    {
        return getmypid();
    }

    public static function setHttpCode($code)
    {
        $header = '';
        switch ($code) {
            case 100:$header = 'Continue';
                break;
            case 101:$header = 'Switching Protocols';
                break;
            case 102:$header = 'Processing';
                break;

            case 200:$header = 'OK';
                break;
            case 201:$header = 'Created';
                break;
            case 202:$header = 'Accepted';
                break;
            case 203:$header = 'Non-Authoritative Information';
                break;
            case 204:$header = 'No Content';
                break;
            case 205:$header = 'Reset Content';
                break;
            case 206:$header = 'Partial Content';
                break;
            case 207:$header = 'Multi-Status';
                break;
            case 208:$header = 'Already Reported';
                break;
            case 226:$header = 'IM Used';
                break;

            case 300:$header = 'Multiple Choices';
                break;
            case 301:$header = 'Moved Permanently';
                break;
            case 302:$header = 'Found';
                break;
            case 303:$header = 'See Other';
                break;
            case 304:$header = 'Not Modified';
                break;
            case 305:$header = 'Use Proxy';
                break;
            case 306:$header = 'Switch Proxy';
                break;
            case 307:$header = 'Temporary Redirect';
                break;
            case 308:$header = 'Permanent Redirect';
                break;

            case 400:$header = 'Bad Request';
                break;
            case 401:$header = 'Unauthorized';
                break;
            case 402:$header = 'Payment Required';
                break;
            case 403:$header = 'Forbidden';
                break;
            case 404:$header = 'Not Found';
                break;
            case 405:$header = 'Method Not Allowed';
                break;
            case 406:$header = 'Not Acceptable';
                break;
            case 407:$header = 'Proxy Authentication Required';
                break;
            case 408:$header = 'Request Timeout';
                break;
            case 409:$header = 'Conflict';
                break;
            case 410:$header = 'Gone';
                break;
            case 411:$header = 'Length Required';
                break;
            case 412:$header = 'Precondition Failed';
                break;
            case 413:$header = 'Payload Too Large';
                break;
            case 414:$header = 'URI Too Long';
                break;
            case 415:$header = 'Unsupported Media Type';
                break;
            case 416:$header = 'Range Not Satisfiable';
                break;
            case 417:$header = 'Expectation Failed';
                break;
            case 418:$header = 'I\'m a teapot';
                break;
            case 421:$header = 'Misdirected Request';
                break;
            case 422:$header = 'Unprocessable Entity';
                break;
            case 423:$header = 'Locked';
                break;
            case 424:$header = 'Failed Dependency';
                break;
            case 426:$header = 'Upgrade Required';
                break;
            case 428:$header = 'Precondition Required';
                break;
            case 429:$header = 'Too Many Requests';
                break;
            case 431:$header = 'Request Header Fields Too Large';
                break;
            case 451:$header = 'Unavailable For Legal Reasons';
                break;

            case 500:$header = 'Internal Server Error';
                break;
            case 501:$header = 'Not Implemented';
                break;
            case 502:$header = 'Bad Gateway';
                break;
            case 503:$header = 'Service Unavailable';
                break;
            case 504:$header = 'Gateway Timeout';
                break;
            case 505:$header = 'HTTP Version Not Supported';
                break;
            case 506:$header = 'Variant Also Negotiates';
                break;
            case 507:$header = 'Insufficient Storage';
                break;
            case 508:$header = 'Loop Detected';
                break;
            case 510:$header = 'Not Extended';
                break;
            case 511:$header = 'Network Authentication Required';
                break;
        }
        if ($header) {
            self::setHeader("HTTP/1.0 {$code} {$header}");
        }
    }

    public static function setHeader($name, $value = null)
    {
        if (null !== $value) {
            header("{$name}: {$value}");
        } else {
            header("{$name}");
        }
    }

    public static function setMimeType($type, $charset = null)
    {
        if ($charset) {
            self::setHeader('content-type', $type.'; charset='.$charset);
        } else {
            self::setHeader('content-type', $type);
        }
    }

    public static function setLength($length)
    {
        self::setHeader('Content-Length', $length);
    }

    public static function getHeader(string $name)
    {
        $name = strtoupper(str_replace('-', '_', $name));

        return isset($_SERVER['HTTP_'.$name]) ? $_SERVER['HTTP_'.$name] : null;
    }

    public static function tojson($charset = 'utf-8')
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
        self::setMimeType('application/json', $charset);
    }

    public static function is_safe_referer(string $referer = '')
    {
        if (!$referer) {
            $referer = isset(self::$request['referer']) ? self::$request['referer'] : '';
        }
        if ($referer) {
            if (preg_match("/\w+:\\/\\/.*/", $referer)) {
                $url = parse_url($referer);
                $hostname = $url['host'];
                if (isset($url['port'])) {
                    $hostname .= ':'.$url['port'];
                }
                if (isset(self::$request['hostname']) and self::$request['hostname'] == $hostname) {
                    return true;
                } elseif ($safe_referers = Options::get('packages.base.safe_referers') and in_array($hostname, $safe_referers)) {
                    return true;
                }
            } elseif ('/' == substr($referer, 0, 1)) {
                return true;
            }
        }

        return false;
    }

    public static function makeFilesStandard($files)
    {
        $walk = function ($arr, $key) use (&$walk) {
            $ret = [];
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $ret[$k] = $walk($v, $key);
                } else {
                    $ret[$k][$key] = $v;
                }
            }

            return $ret;
        };

        $arr = [];
        foreach ($files as $name => $values) {
            if (!isset($arr[$name])) {
                $arr[$name] = [];
            }

            if (!is_array($values['error'])) {
                // normal syntax
                $arr[$name] = $values;
            } else {
                // html array feature
                foreach ($values as $attribute_key => $attribute_values) {
                    $arr[$name] = array_replace_recursive($arr[$name], $walk($attribute_values, $attribute_key));
                }
            }
        }

        return $arr;
    }
}
