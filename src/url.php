<?php

namespace packages\base;

use Illuminate\Support\Facades\Request;
use Illuminate\Translation\Translator;

function url(string $page = '', array $parameters = [], bool $absolute = false): string
{
    $langInUrl = (Options::get('packages.base.translator.changelang') == 'uri');
    if ('.' == $page) {
        $page = Http::$request['uri'];
        if ($langInUrl) {
            $page = ltrim($page, '/');
            $firstSlash = strpos($page, '/');
            if (false !== $firstSlash) {
                $page = substr($page, $firstSlash + 1);
            }
        }
    }

    $lastSlash = Options::get('packages.base.routing.lastslash');
    if (true == $lastSlash) {
        if ('/' != substr($page, -1)) {
            $page .= '/';
        }
    } else {
        while ('/' == substr($page, -1)) {
            $page = substr($page, 0, strlen($page) - 1);
        }
    }
    $encode = isset($parameters['@encode']) and $parameters['@encode'];
    if ($encode) {
        unset($parameters['@encode']);
    }
    $url = '';
    if ($absolute) {
        if (isset($parameters['hostname'])) {
            trigger_error("'hostname' parameter is deprecated, use '@hostname' instead", E_USER_DEPRECATED);
            $hostname = $parameters['hostname'];
            unset($parameters['hostname']);
        } elseif (isset($parameters['@hostname'])) {
            $hostname = $parameters['@hostname'];
            unset($parameters['@hostname']);
        } else {
            $hostname = Request::getHttpHost();
        }
        $url .= Request::getScheme().'://'.$hostname;
    }

    if ($langInUrl) {
        $lang = '';
        if (isset($parameters['lang'])) {
            trigger_error("'lang' parameter is deprecated, use '@lang' instead", E_USER_DEPRECATED);
            $lang = $parameters['lang'];
            unset($parameters['lang']);
        } elseif (isset($parameters['@lang'])) {
            $lang = $parameters['@lang'];
            unset($parameters['@lang']);
        } else {
            $lang = app()->getLocale();
        }
        if (!$page) {
            $url .= '/'.$lang;
        } elseif ($lang) {
            $url .= '/'.$lang;
        }
    } else {
        unset($parameters['@lang'], $parameters['lang']);
    }
    if ($page) {
        if ($encode) {
            $page = str_replace('%2F', '/', urlencode($page));
        }
        $url .= '/'.$page;
    }
    if (!$url) {
        $url .= '/';
    }
    if (is_array($parameters) and $parameters) {
        $url .= '?'.http_build_query($parameters);
    }

    return $url;
}
