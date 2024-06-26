<?php

namespace packages\base\router;

use packages\base\options;
use packages\base\router;

class Rule
{
    public const post = 'post';
    public const get = 'get';
    public const put = 'put';
    public const delete = 'delete';
    public const http = 'http';
    public const https = 'https';
    public const api = 'api';
    public const ajax = 'ajax';

    /**
     * Construct a new rule from array.
     *
     * @param array $data required indexes:
     *                    controller(string|string[])
     *                    indexes are optional:
     *                    name(string)
     *                    method(string|string[])
     *                    path(string|string[]|array)
     *                    absolute(bool)
     *                    domain(string|string[])
     *                    scheme(string|string[])
     *                    regex(string)
     *                    middleware(string[][])
     *                    exceptions(string[]) FQCNs of exceptions
     *
     * @return packages\base\router\rule
     *
     * @throws packages\base\router\MethodException       {@see rule::addMethod()}
     * @throws packages\base\router\PathException         {@see rule::setPath()}
     * @throws packages\base\router\RulePartNameException {@see rule::validPart()}
     * @throws packages\base\router\RouterRulePart        {@see rule::validPart()}
     * @throws packages\base\router\RulePartValue         {@see rule::validPart()}
     * @throws packages\base\router\DomainException       {@see rule::addDomain()}
     * @throws packages\base\router\SchemeException       {@see rule::addScheme()}
     * @throws packages\base\router\InvalidRegexException {@see rule::setRegex()}
     * @throws packages\base\router\ControllerException   {@see rule::setController()}
     * @throws packages\base\router\PermissionException   {@see rule::allow()}
     * @throws packages\base\router\PermissionException   {@see rule::deny()}
     * @throws packages\base\router\PermissionException   {@see rule::addPermissonController()}
     * @throws packages\base\router\ControllerException   {@see rule::addPermissonController()}
     */
    public static function import(array $data): rule
    {
        $rule = new rule();
        if (isset($data['name'])) {
            $rule->setName($data['name']);
        }
        if (isset($data['method'])) {
            if (is_array($data['method'])) {
                foreach ($data['method'] as $method) {
                    $rule->addMethod($method);
                }
            } elseif (is_string($data['method'])) {
                $rule->addMethod($data['method']);
            }
        }
        if (isset($data['path'])) {
            $rule->setPath($data['path']);

            if (isset($data['absolute'])) {
                $rule->setAbsolute($data['absolute']);
            }
            if (isset($data['domain'])) {
                if (is_array($data['domain'])) {
                    foreach ($data['domain'] as $domain) {
                        $rule->addDomain($domain);
                    }
                } else {
                    $rule->addDomain($data['domain']);
                }
            }
            if (isset($data['scheme'])) {
                if (is_array($data['scheme'])) {
                    foreach ($data['scheme'] as $scheme) {
                        $rule->addScheme($scheme);
                    }
                } else {
                    $rule->addScheme($data['scheme']);
                }
            }
        } elseif (isset($data['regex'])) {
            $rule->setRegex($data['regex']);
        }
        if (is_string($data['controller'])) {
            $data['controller'] = explode('@', $data['controller'], 2);
        }
        $rule->setController($data['controller'][0], $data['controller'][1]);

        if (isset($data['middleware'])) {
            if (!is_array($data['middleware'])) {
                $data['middleware'] = [$data['middleware']];
            }
            foreach ($data['middleware'] as $middleware) {
                if (is_string($middleware)) {
                    $middleware = explode('@', $middleware, 2);
                }
                $rule->addMiddleware($middleware[0], $middleware[1]);
            }
        }
        if (isset($data['permissions'])) {
            foreach ($data['permissions'] as $permission => $controller) {
                if (true === $controller) {
                    $rule->allow($permission);
                } elseif (false === $controller) {
                    $rule->deny($permission);
                } else {
                    $controller = explode('@', $controller, 2);
                    $rule->addPermissonController($permission, $controller[0], $controller[1]);
                }
            }
        }
        if (isset($data['exceptions'])) {
            foreach ($data['exceptions'] as $exception) {
                $rule->addException($exception);
            }
        }

        return $rule;
    }

    /**
     * Construct a rule from string or array.
     *
     * @param array|string $part
     *
     * @return array
     *
     * @throws packages\base\router\RouterRulePart        if part was not string or array
     * @throws packages\base\router\RulePartNameException if array part hasn't "name" index
     * @throws packages\base\router\RouterRulePart        if array part hasn't "type" index or was invalid
     * @throws packages\base\router\RouterRulePart        if array part has invalid "regex" index
     * @throws packages\base\router\RulePartValue         if array part has empty "values" index or miss-filled
     */
    private static function validPart($part)
    {
        if (is_numeric($part)) {
            return [
                'type' => 'static',
                'name' => $part,
            ];
        }
        if (is_string($part)) {
            if (!preg_match('/^:([a-zA-Z0-9_]+)(\\.\\.\\.)?$/', $part, $matches)) {
                return [
                    'type' => 'static',
                    'name' => $part,
                ];
            }

            return [
                'type' => isset($matches[2]) ? 'wildcard' : 'dynamic',
                'name' => $matches[1],
            ];
        }
        if (!is_array($part)) {
            throw new RouterRulePart($part);
        }
        if (!isset($part['name'])) {
            throw new RulePartNameException($part);
        }
        if (!isset($part['type']) and (isset($part['regex']) or isset($part['values']))) {
            $part['type'] = 'dynamic';
        }
        if (!isset($part['type'])) {
            throw new RouterRulePart($part, 'type is assigned');
        }
        if (!in_array($part['type'], ['static', 'dynamic', 'wildcard'])) {
            throw new RouterRulePart($part, 'type is not static or dynamic or wildcard');
        }
        if ('dynamic' == $part['type']) {
            if (isset($part['regex'])) {
                if (false === @preg_match($part['regex'], null)) {
                    throw new RouterRulePart($part, 'regex is invalid');
                }
            } elseif (isset($part['values'])) {
                if (is_array($part['values']) and !empty($part['values'])) {
                    foreach ($part['values'] as $value) {
                        if (!is_string($value) and !is_numeric($value)) {
                            throw new RulePartValue($part);
                        }
                    }
                } else {
                    throw new RulePartValue($part);
                }
            }
        }
        $valid = [
            'type' => $part['type'],
            'name' => $part['name'],
        ];
        if ('dynamic' == $part['type']) {
            if (isset($part['regex'])) {
                $valid['regex'] = $part['regex'];
            } elseif (isset($part['values'])) {
                $valid['values'] = $part['values'];
            }
        }

        return $valid;
    }

    /** @var string|null */
    private $name;

    /** @var string[] */
    private $methods = [];

    /** @var array|null */
    private $path;

    /** @var string|null */
    private $regex;

    /** @var string[] */
    private $domains = [];

    /** @var array */
    private $permissions = [
        self::ajax => true,
    ];

    /** @var array */
    private $middlewares = [];

    /** @var bool */
    private $absolute = false;

    /** @var string[] */
    private $controller;

    /** @var string[] */
    private $schemes = [];

    /** @var string[] */
    private $exceptions = [];

    /** @var int */
    private $wildcards = 0;

    /** @var int */
    private $dynamics = 0;

    /**
     * Allow a http method.
     *
     * @param string $method http method which should be "post", "get", "put", "delete"
     *
     * @throws packages\base\router\MethodException if method was invalid
     */
    public function addMethod(string $method): void
    {
        $method = strtolower($method);
        if (in_array($method, $this->methods)) {
            return;
        }
        if (!in_array($method, [self::post, self::get, self::put, self::delete])) {
            throw new MethodException($method);
        }
        $this->methods[] = $method;
    }

    /**
     * @return array http methods for this rule
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Setter for path.
     *
     * @param string|array $path
     *
     * @throws packages\base\router\PathException         if path wasn't array nor string
     * @throws packages\base\router\RulePartNameException {@see rule::validPart()}
     * @throws packages\base\router\RouterRulePart        {@see rule::validPart()}
     * @throws packages\base\router\RulePartValue         {@see rule::validPart()}
     */
    public function setPath($path): void
    {
        if (is_string($path)) {
            $path = explode('/', $path);
        } elseif (!is_array($path)) {
            throw new PathException($this, $path, 'path must be string or array');
        }
        $this->path = [];
        $this->wildcards = 0;
        $this->dynamics = 0;
        foreach ($path as $x => $part) {
            if ($part) {
                $valid = self::validPart($part);
                if ('wildcard' == $valid['type']) {
                    ++$this->wildcards;
                    ++$this->dynamics;
                } elseif ('dynamic' == $valid['type']) {
                    ++$this->dynamics;
                }
                $this->path[] = $valid;
            }
        }
    }

    /**
     * @return array of arrays that each array has 'type' and 'name' indexes
     */
    public function getPath(): ?array
    {
        return $this->path;
    }

    /**
     * Setter for regex.
     *
     * @throws packages\base\router\InvalidRegexException if regex was invalid
     */
    public function setRegex(string $regex): void
    {
        if (false === @preg_match($regex, null)) {
            throw new InvalidRegexException($regex, $this);
        }
        $this->regex = $regex;
    }

    /**
     * Setter for controller.
     *
     * @param string $class  full qualified class name
     * @param string $method name of method in the class
     *
     * @throws packages\base\router\ControllerException if class or method doesn't exists
     */
    public function setController(string $class, string $method): void
    {
        if (!method_exists($class, $method)) {
            throw new ControllerException($class.'@'.$method);
        }
        $this->controller = [$class, $method];
    }

    /**
     * Getter for controller.
     *
     * @return string[]|null first index is FQCN and second is method name
     */
    public function getController(): ?array
    {
        return $this->controller;
    }

    /**
     * Setter for absolute.
     */
    public function setAbsolute(bool $absolute): void
    {
        $this->absolute = $absolute;
    }

    /**
     * Getter for absolute.
     */
    public function isAbsolute(): bool
    {
        return $this->absolute;
    }

    /**
     * Return true when regex is set.
     */
    public function isRegex(): bool
    {
        return !empty($this->regex);
    }

    /**
     * Allow an scheme.
     *
     * @param string $scheme should be "http" or "https"
     *
     * @throws packages\base\router\SchemeException if scheme was invalid
     */
    public function addScheme(string $scheme): void
    {
        $scheme = strtolower($scheme);
        if (in_array($scheme, $this->schemes)) {
            return;
        }
        if (!in_array($scheme, [self::http, self::https])) {
            throw new SchemeException($this);
        }
        $this->schemes[] = $scheme;
    }

    /**
     * Accept a domain.
     *
     * @param string $domain should be domain name, It could be a regex too
     *
     * @throws packages\base\router\DomainException if regex-domain was invalid
     */
    public function addDomain(string $domain): void
    {
        if ('/' == substr($domain, 1) and '/' == substr($domain, -1)) {
            if (false === @preg_match($domain, null)) {
                throw new DomainException($this);
            }
        } elseif ('www.' == substr($domain, 0, 4)) {
            $domain = substr($domain, 4);
        }
        $this->domains[] = $domain;
    }

    /**
     * Allow a permission.
     *
     * @param string $permission should be "api" or "ajax"
     *
     * @throws packages\base\router\PermissionException if permission was invalid
     */
    public function allow(string $permission): void
    {
        if (!in_array($permission, [self::api, self::ajax])) {
            throw new PermissionException($permission);
        }
        $this->permissions[$permission] = true;
    }

    /**
     * Deny a permission.
     *
     * @param string $permission should be "api" or "ajax"
     *
     * @throws packages\base\router\PermissionException if permission was invalid
     */
    public function deny(string $permission): void
    {
        if (!in_array($permission, [self::api, self::ajax])) {
            throw new PermissionException($permission);
        }
        $this->permissions[$permission] = false;
    }

    /**
     * Set a controller for a permission.
     *
     * @param string $permission should be "api" or "ajax"
     * @param string $class      full qualified class name
     * @param string $method     name of method in the class
     *
     * @throws packages\base\router\PermissionException if permission was invalid
     * @throws packages\base\router\ControllerException if class or method not exists
     */
    public function addPermissonController(string $permission, string $class, string $method): void
    {
        if (!in_array($permission, [self::api, self::ajax])) {
            throw new PermissionException($permission);
        }
        if (!method_exists($class, $method)) {
            throw new ControllerException("{$class}@{$method}");
        }
        $this->permissions[$permission] = [$class, $method];
    }

    /**
     * Add a middleware.
     *
     * @param string $class  full qualified class name
     * @param string $method name of method in the class
     */
    public function addMiddleware(string $class, string $method): void
    {
        if (!method_exists($class, $method)) {
            throw new ruleMiddlewareException("{$class}@{$method}");
        }
        $this->middlewares[] = [$class, $method];
    }

    /**
     * Return count of wildcard parts.
     */
    public function wildcardParts(): int
    {
        return $this->wildcards;
    }

    /**
     * Return count of dynamic parts.
     */
    public function dynamicParts(): int
    {
        return $this->dynamics;
    }

    /**
     * Return count of all parts.
     */
    public function parts(): int
    {
        return !$this->path ? 0 : (is_array($this->path) ? count($this->path) : 1);
    }

    /**
     * Run middlewares.
     * If one of them return false, it will not run others.
     *
     * @param array|null $data will pass to middleware method
     */
    public function runMiddlewares(?array $data): void
    {
        foreach ($this->middlewares as $middleware) {
            $class = new $middleware[0]();
            $method = $middleware[1];
            if (false === $class->$method($data)) {
                return;
            }
        }
    }

    /**
     * Check this rules agianst the given paramters.
     * If match, it will return true or a non-empty array.
     *
     * @return bool|array
     */
    public function check(string $method, string $scheme, string $domain, string $url, ?array $data)
    {
        if (!empty($this->methods) and !in_array(strtolower($method), $this->methods)) {
            return false;
        }
        if (!empty($this->regex)) {
            if (!preg_match($this->regex, $scheme.'://'.$domain.$url, $matches)) {
                return false;
            }
            if (!$this->checkPermissions($data)) {
                return false;
            }

            return count($matches) > 1 ? $matches : true;
        }
        if (!empty($this->schemes) and !in_array(strtolower($scheme), $this->schemes)) {
            return false;
        }
        if (empty($this->domains)) {
            $this->domains = router::getDefaultDomains();
        }
        if (!empty($this->domains) and !in_array('*', $this->domains)) {
            $domain = strtolower($domain);
            if ('www.' == substr($domain, 0, 4)) {
                $domain = substr($domain, 4);
            }
            if (!in_array($domain, $this->domains)) {
                $foundomain = false;
                foreach ($this->domains as $item) {
                    if ('/' == substr($item, 0, 1) and '/' == substr($item, -1)) {
                        if (@preg_match($domain, $item)) {
                            $foundomain = true;
                        }
                    }
                }
                if (!$foundomain) {
                    return false;
                }
            }
        }
        $url = explode('/', trim(urldecode($url), '/'));
        if ('' == $url[0]) {
            $url = array_slice($url, 1);
        }
        $lang = null;
        if (!$this->absolute) {
            $changelang = options::get('packages.base.translator.changelang');
            if ('uri' == $changelang) {
                if (!empty($url[0])) {
                    $lang = router::CheckShortLang($url[0], empty($this->exceptions));
                    if ($lang) {
                        $url = array_slice($url, 1);
                    }
                } else {
                    $url = array_slice($url, 1);
                }
            }
        }
        $checkPath = $this->checkPath($url);
        if (!$checkPath) {
            return false;
        }
        if (!$this->checkPermissions($data)) {
            return false;
        }
        if ($lang) {
            if (true === $checkPath) {
                $checkPath = [];
            }
            $checkPath['@lang'] = $lang;
        }

        return $checkPath;
    }

    /**
     * Add exception to handle.
     *
     * @param string $exception Full qQualified class name
     */
    public function addException(string $exception): void
    {
        if (!in_array($exception, $this->exceptions)) {
            $this->exceptions[] = $exception;
        }
    }

    /**
     * Getter for exceptions.
     *
     * @return string[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    public function __serialize(): array
    {
        $data = [];
        foreach (['name', 'controller', 'methods', 'path', 'regex', 'domains', 'permissions', 'middlewares', 'absolute', 'schemes', 'exceptions', 'wildcards', 'dynamics'] as $key) {
            $data[$key] = $this->{$key};
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        foreach (['name', 'controller', 'methods', 'path', 'regex', 'domains', 'permissions', 'middlewares', 'absolute', 'schemes', 'exceptions', 'wildcards', 'dynamics'] as $key) {
            $this->{$key} = $data[$key];
        }
    }

    /**
     * Get the value of name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Check given url against this rule path.
     * If match, it will return true or a non-empty array.
     *
     * @param string $url exploded url which not contain language code
     *
     * @return bool|array
     */
    private function checkPath(array $url)
    {
        $data = [];
        $lastwildcard = null;
        $urlx = 0;
        $urlen = count($url);
        foreach ($this->path as $x => $part) {
            if ('wildcard' == $part['type']) {
                if (isset($this->path[$x + 1])) {
                    $firstUrlx = $urlx;
                    $nextPart = $this->path[$x + 1];
                    $found = false;
                    for ($ux = $urlx + 1; $ux < $urlen; ++$ux) {
                        if ($this->checkPartPath($nextPart, $url[$ux])) {
                            $urlx = $ux - 1;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        return false;
                    }
                    $data[$part['name']] = implode('/', array_slice($url, $firstUrlx, $urlx - $firstUrlx + 1));
                } else {
                    $data[$part['name']] = implode('/', array_slice($url, $urlx));
                    $urlx = $urlen - 1;
                }
            } else {
                if (isset($url[$urlx]) and $check = $this->checkPartPath($part, $url[$urlx])) {
                    if (is_array($check)) {
                        $data = array_replace($data, $check);
                    }
                } else {
                    return false;
                }
            }
            ++$urlx;
        }
        if (empty($this->exceptions) and $urlen != $urlx) {
            return false;
        }

        return $data ? $data : true;
    }

    /**
     * @return bool|array
     */
    private function checkPartPath(array $part, string $url)
    {
        $data = [];
        if ('static' == $part['type']) {
            if ($part['name'] != $url) {
                return false;
            }
        } elseif ($part['type'] = 'dynamic') {
            if (isset($part['regex'])) {
                if (!preg_match($part['regex'], $url)) {
                    return false;
                }
            } elseif (isset($part['values'])) {
                if (!in_array($url, $part['values'])) {
                    return false;
                }
            }
            $data[$part['name']] = $url;
        } else {
            return false;
        }

        return $data ? $data : true;
    }

    /**
     * Check a permission, and if permission has a controller, it will call.
     */
    private function askPermission(string $permission): bool
    {
        if (!isset($this->permissions[$permission])) {
            return false;
        }
        if (is_array($this->permissions[$permission])) {
            $class = new $this->permissions[$permission][0]();

            return boolval($class->{$this->permissions[$permission][1]}());
        }

        return $this->permissions[$permission];
    }

    /**
     * Check permissions according to requested paramaters in data and permissions in rule.
     *
     * @param array router given data
     */
    private function checkPermissions(array $data): bool
    {
        return $this->checkAPIPermission($data) and $this->checkAjaxPermission($data);
    }

    /**
     * Check api permission if requested in url.
     */
    private function checkAPIPermission(array $data): bool
    {
        return !isset($data['api']) or $this->askPermission(self::api);
    }

    /**
     * Check ajax permission if requested in url.
     *
     * @return bool
     */
    private function checkAjaxPermission($data)
    {
        return !isset($data['ajax']) or $this->askPermission(self::ajax);
    }
}
