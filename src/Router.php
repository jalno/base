<?php

namespace packages\base;

use packages\base\Router\Rule;
use packages\base\Translator\InvalidLangCode;
use packages\base\View\Error;

class Router
{
    private static $activeRule;
    private static $rules = [];
    private static $exceptions = [];
    private static $hostname;
    private static $scheme;
    private static $defaultDomains;
    private static $isDefaultDomain;

    public static function getActiveRule(): ?Rule
    {
        return self::$activeRule;
    }

    public static function getDefaultDomains()
    {
        if (!self::$defaultDomains) {
            $log = Log::getInstance();
            $log->debug('looking for packages.base.router.defaultDomain option');
            $option = Options::get('packages.base.router.defaultDomain');
            if ($option) {
                $log->reply($option);
                if (!is_array($option)) {
                    $option = [$option];
                }
                self::$defaultDomains = $option;
            } elseif (isset(HTTP::$server['hostname'])) {
                $log->reply('use server hostname:', HTTP::$server['hostname']);
                self::$defaultDomains = [HTTP::$server['hostname']];
            } else {
                $log->reply()->warn('notfound');
            }
        }

        return self::$defaultDomains;
    }

    public static function isDefaultDomain()
    {
        if (null === self::$isDefaultDomain) {
            $domain = strtolower(HTTP::$request['hostname']);
            if ('www.' == substr($domain, 0, 4)) {
                $domain = substr($domain, 4);
            }
            self::$isDefaultDomain = in_array($domain, Router::getDefaultDomains());
        }

        return self::$isDefaultDomain;
    }

    public static function addRule(Rule $rule)
    {
        self::$rules[] = $rule;
    }

    public static function resetRules()
    {
        self::$rules = [];
    }

    public static function CheckShortLang($lang, bool $throwError = true)
    {
        $log = Log::getInstance();
        $log->debug('looking for packages.base.translator.changelang.type option');
        $type = Options::get('packages.base.translator.changelang.type') ?: 'short';
        $log->reply($type);
        if ('short' == $type) {
            $log->debug('check', $lang);
            if (Translator::is_shortCode($lang)) {
                $log->reply('valid shortcode');
                $langs = Translator::getAvailableLangs();
                $log->debug('Available languages: ', $langs);
                foreach ($langs as $l) {
                    if (substr($l, 0, 2) == $lang) {
                        $lang = $l;
                        break;
                    }
                }
            } else {
                $log->reply()->debug('invalid');
                if ($throwError) {
                    throw new InvalidLangCode();
                }

                return;
            }
        }

        return $lang;
    }

    public static function gethostname()
    {
        if (!isset(HTTP::$request['hostname'])) {
            return null;
        }
        $log = Log::getInstance();
        if (!self::$hostname) {
            $log->debug('looking for packages.base.routing.www option');
            $www = Options::get('packages.base.routing.www');
            $log->reply($www);
            $hostname = HTTP::$request['hostname'];
            if ('nowww' == $www) {
                if ('www.' == substr($hostname, 0, 4)) {
                    $hostname = substr($hostname, 4);
                }
            } elseif ('withwww' == $www) {
                if ('www.' != substr($hostname, 0, 4)) {
                    $hostname = 'www.'.$hostname;
                }
            }
            self::$hostname = $hostname;
        }
        $log->debug('hostname should be', self::$hostname);

        return self::$hostname;
    }

    public static function getscheme()
    {
        $log = Log::getInstance();
        if (!self::$scheme) {
            $log->debug('looking for packages.base.routing.scheme');
            $schemeoption = Options::get('packages.base.routing.scheme');
            $log->reply($schemeoption);
            $scheme = HTTP::$request['scheme'] ?? '';
            if ($schemeoption and $scheme != $schemeoption) {
                $scheme = $schemeoption;
            }
            self::$scheme = $scheme;
        }
        $log->debug('scheme should be', self::$scheme);

        return self::$scheme;
    }

    public static function checkwww()
    {
        $log = Log::getInstance();
        $hostname = HTTP::$request['hostname'];
        $log->debug('hostname is', $hostname);
        $hostnameoption = self::gethostname();
        if ($hostnameoption != $hostname) {
            $hostname = $hostnameoption;
            $newurl = self::getscheme().'://'.$hostname.HTTP::$request['uri'];
            $log->debug('redirect to', $newurl);
            HTTP::redirect($newurl);

            return false;
        }

        return true;
    }

    public static function checkscheme()
    {
        $log = Log::getInstance();
        $scheme = HTTP::$request['scheme'];
        $log->debug('scheme is', $scheme);
        $schemeoption = self::getscheme();
        if ($schemeoption != $scheme) {
            $scheme = $schemeoption;
            $newurl = $scheme.'://'.self::gethostname().HTTP::$request['uri'];
            $log->debug('redirect to', $newurl);
            HTTP::redirect($newurl);

            return false;
        }

        return true;
    }

    public static function checkLastSlash()
    {
        if (strlen(HTTP::$request['uri']) > 1) {
            $log = Log::getInstance();
            $option = Options::get('packages.base.routing.lastslash');
            if (null !== $option) {
                $lastchar = substr(HTTP::$request['uri'], -1);
                if ($option) {
                    $log->debug('should have last slash');
                    if ('/' != $lastchar) {
                        $log->reply('it does not');
                        $newurl = self::getscheme().'://'.self::gethostname().HTTP::$request['uri'].'/';
                        $log->debug('redirect to', $newurl);
                        HTTP::redirect($newurl);

                        return false;
                    }
                } else {
                    $log->debug('should have not last slash');
                    if ('/' == $lastchar) {
                        $log->reply('it does');
                        $uri = HTTP::$request['uri'];
                        while ('/' == substr($uri, -1)) {
                            $uri = substr($uri, 0, strlen($uri) - 1);
                        }
                        $newurl = self::getscheme().'://'.self::gethostname().$uri;
                        $log->debug('redirect to', $newurl);
                        HTTP::redirect($newurl);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    private static function sortRules(&$rules)
    {
        usort($rules, function ($a, $b) {
            $a_wildcards = $a->wildcardParts();
            $b_wildcards = $b->wildcardParts();
            if ($a_wildcards != $b_wildcards) {
                return $a_wildcards - $b_wildcards;
            }
            $a_dynamics = $a->dynamicParts();
            $b_dynamics = $b->dynamicParts();
            if ($a_dynamics != $b_dynamics) {
                return $a_dynamics - $b_dynamics;
            }

            return $b->parts() - $a->parts();
        });
    }

    public static function checkRules(&$rules, $uri = null, ?\Throwable $exception = null)
    {
        $log = Log::getInstance();
        if (null === $uri) {
            $uri = HTTP::$request['uri'];
        }
        $log->info('method:', HTTP::$request['method']);
        $log->info('scheme:', HTTP::$request['scheme']);
        $log->info('hostname:', HTTP::$request['hostname']);
        $log->info('uri:', $uri);
        $log->info('url parameters:', HTTP::$request['get']);
        foreach ($rules as $x => $rule) {
            $log->info("check in {$x}th rule");
            $data = $rule->check(HTTP::$request['method'], HTTP::$request['scheme'], HTTP::$request['hostname'], $uri, HTTP::$request['get']);
            if (false !== $data) {
                $data = is_array($data) ? $data : [];
                self::$activeRule = $rule;
                $log->reply('matched');
                $log->debug('URL data:', $data);
                if (isset($data['@lang'])) {
                    $log->info('translator language changed to', $data['@lang']);
                    Translator::setLang($data['@lang']);
                    Packages::registerTranslates($data['@lang']);
                }
                list($controller, $method) = $rule->getController();
                $log->debug('run middlewares');
                $rule->runMiddlewares($data);
                $log->info('call', $controller.'@'.$method);
                $controllerClass = new $controller();
                try {
                    $args = [$data];
                    if ($exception) {
                        $args[] = $exception;
                    }
                    $response = $controllerClass->$method(...$args);
                } catch (InputValidationException $e) {
                    $response = $controllerClass->getResponse();
                    if (!$response) {
                        throw $e;
                    }
                    $response->setStatus(false);
                    $error = Views\FormError::fromException($e);
                    $view = $response->getView();
                    if ($view instanceof Views\Form) {
                        $view->setFormError($error);
                        $view->setDataForm(HTTP::$request['post']);
                    } else {
                        $error->setTraceMode(Error::NO_TRACE);
                        $response->setData([
                            'error' => [$error],
                        ]);
                    }
                } catch (DB\DuplicateRecord $e) {
                    $response = $controllerClass->getResponse();
                    if (!$response) {
                        throw $e;
                    }
                    $response->setStatus(false);
                    $error = Views\FormError::fromException($e);
                    $view = $response->getView();
                    if ($view instanceof views\form) {
                        $view->setFormError($error);
                        $view->setDataForm(HTTP::$request['post']);
                    } else {
                        $error->setTraceMode(Error::NO_TRACE);
                        $response->setData([
                            'error' => [$error],
                        ]);
                    }
                } catch (Error $e) {
                    $response = $controllerClass->getResponse();
                    if (!$response) {
                        throw $e;
                    }
                    $response->setStatus(false);
                    $view = $response->getView();
                    if ($view) {
                        $view->addError($e);
                    } else {
                        $e->setTraceMode(Error::NO_TRACE);
                        $response->setData([
                            'error' => [$e],
                        ]);
                    }
                }
                $log->reply('Success');
                if ($response) {
                    $log->info('send response');
                    $response->send();
                    $log->reply('Success');
                }

                return true;
            } else {
                $log->reply('not matched');
            }
        }

        return false;
    }

    public static function routing()
    {
        $log = Log::getInstance();
        $found = false;
        $api = Loader::sapi();
        $log->debug('SAPI:', $api);
        if (Loader::cgi == $api) {
            $hostname = HTTP::$request['hostname'];
            if ('www.' == substr($hostname, 0, 4)) {
                $hostname = substr($hostname, 4);
            }
            $log->debug('check', $hostname, 'in default domains');
            $defaultDomains = self::getDefaultDomains();
            if (in_array($hostname, self::getDefaultDomains())) {
                $log->reply('Found');
                $log->debug('check www');
                $checkwww = self::checkwww();
                $log->reply($checkwww);
                $log->debug('check scheme');
                $checkscheme = self::checkscheme();
                $log->reply($checkscheme);
                if (!$checkwww or !$checkscheme) {
                    return false;
                }
            }
            if (!self::checkLastSlash()) {
                return false;
            }
            $log->debug('separate absolute and regex rules');
            $absoluteRules = [];
            $regexRules = [];
            $normalRules = [];
            foreach (self::$rules as $rule) {
                if ($rule->getExceptions()) {
                    continue;
                }
                if ($rule->isAbsolute()) {
                    $absoluteRules[] = $rule;
                } elseif ($rule->isRegex()) {
                    $regexRules[] = $rule;
                } else {
                    $normalRules[] = $rule;
                }
            }
            $log->reply(count($absoluteRules), 'absolute rules,', count($normalRules), 'normal rule', count($regexRules), 'regex rules');
            try {
                $log->debug('sort absolute rules');
                self::sortRules($absoluteRules);
                $log->reply('Success');
                $log->debug('check in absolute rules');
                $found = self::checkRules($absoluteRules);
                if ($found) {
                    $log->reply('Found');
                } else {
                    $log->reply('Notfound');

                    $uri = rtrim(HTTP::$request['uri'], '/');
                    $log->debug('sort normal rules');

                    try {
                        self::sortRules($normalRules);
                        $log->reply('Success');
                        $log->debug('check in normal rules');
                        $found = self::checkRules($normalRules, $uri);
                        if ($found) {
                            $log->reply('Found');
                        } else {
                            $log->reply('Notfound');
                        }
                    } catch (InvalidLangCode $e) {
                    }
                    if (!$found) {
                        $log->debug('check in regex rules');
                        $found = self::checkRules($regexRules);
                        if ($found) {
                            $log->reply('Found');
                        } else {
                            $log->reply('Notfound');
                            throw new NotFound();
                        }
                    }
                }
            } catch (\Throwable $e) {
                self::routingExceptions($e);
            }
        } else {
            $processID = CLI::getParameter('process');
            if (!$processID) {
                echo 'Please specify an process ID by passing --process argument'.PHP_EOL;
                exit(1);
            }
            $process = null;
            $processID = str_replace('/', '\\', $processID);
            if (is_numeric($processID)) {
                $process = (new Process())->byId($processID);
            } elseif (preg_match('/^packages\\\\([a-zA-Z0-9_]+\\\\)+([a-zA-Z0-9_]+)\@([a-zA-Z0-9_]+)$/', $processID)) {
                $parameters = CLI::$request['parameters'];
                unset($parameters['process']);
                if (0 == count($parameters)) {
                    $parameters = null;
                }
                $process = new Process();
                $process->name = $processID;
                $process->parameters = $parameters;
                $process->save();
            }
            if (!$process) {
                throw new NotFound();
            }
            $process->run();
        }

        return $found;
    }

    public static function getRules(): array
    {
        return self::$rules;
    }

    private static function routingExceptions(\Throwable $e)
    {
        $api = Loader::sapi();
        if (Loader::cgi != $api) {
            return;
        }
        $absoluteRules = [];
        $regexRules = [];
        $normalRules = [];
        $eClass = get_class($e);
        foreach (self::$rules as $rule) {
            $found = false;
            foreach ($rule->getExceptions() as $exception) {
                if (is_a($e, $exception)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                continue;
            }
            if ($rule->isAbsolute()) {
                $absoluteRules[] = $rule;
            } elseif ($rule->isRegex()) {
                $regexRules[] = $rule;
            } else {
                $normalRules[] = $rule;
            }
        }
        self::sortRules($absoluteRules);
        $found = self::checkRules($absoluteRules, null, $e);
        if (!$found) {
            $uri = rtrim(HTTP::$request['uri'], '/');
            try {
                self::sortRules($normalRules);
                $found = self::checkRules($normalRules, $uri, $e);
            } catch (InvalidLangCode $ee) {
            }
        }
        if (!$found) {
            $found = self::checkRules($regexRules, null, $e);
        }
        if (!$found) {
            throw $e;
        }
    }
}
