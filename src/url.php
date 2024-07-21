<?php

namespace packages\base;

function url($page = '', $parameters = [], $absolute = false)
{
    $changelang = Options::get('packages.base.translator.changelang');
    $type = Options::get('packages.base.translator.changelang.type') ?: 'short';
    if ('.' == $page) {
        $page = HTTP::$request['uri'];
        if ('uri' == $changelang) {
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
        $hostname = '';
        if (isset($parameters['hostname'])) {
            trigger_error("'hostname' parameter is deprecated, use '@hostname' instead", E_USER_DEPRECATED);
            $hostname = $parameters['hostname'];
            unset($parameters['hostname']);
        } elseif (isset($parameters['@hostname'])) {
            $hostname = $parameters['@hostname'];
            unset($parameters['@hostname']);
        } else {
            $hostname = Router::gethostname();
        }
        if (!$hostname and $defaultHostnames = Router::getDefaultDomains()) {
            $hostname = $defaultHostnames[0];
        }
        $url .= Router::getscheme().'://'.$hostname;
    }

    if ('uri' == $changelang) {
        $lang = '';
        if (isset($parameters['lang'])) {
            trigger_error("'lang' parameter is deprecated, use '@lang' instead", E_USER_DEPRECATED);
            $lang = $parameters['lang'];
            unset($parameters['lang']);
        } elseif (isset($parameters['@lang'])) {
            $lang = $parameters['@lang'];
            unset($parameters['@lang']);
        } else {
            if ('short' == $type) {
                $lang = Translator::getShortCodeLang();
            } elseif ('complete' == $type) {
                $lang = Translator::getCodeLang();
            }
        }
        if (!$page) {
            if (2 == strlen($lang)) {
                if ($lang != Translator::getDefaultShortLang()) {
                    $url .= '/'.$lang;
                }
            } elseif ($lang and $lang != Translator::getDefaultLang()) {
                $url .= '/'.$lang;
            }
        } elseif ($lang) {
            $url .= '/'.$lang;
        }
    } elseif ('parameter' == $changelang) {
        if (!isset($parameters['@lang'])) {
            if ('short' == $type) {
                $parameters['@lang'] = Translator::getShortCodeLang();
            } elseif ('complete' == $type) {
                $parameters['@lang'] = Translator::getCodeLang();
            }
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
