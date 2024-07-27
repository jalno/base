<?php
namespace packages\base;

use Exception;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use packages\base\Router\RouterRulePart;
use packages\base\Router\RulePartNameException;
use packages\base\Router\RulePartValue;

class RouteFactory
{
	private array $wheres = [];
	public function __construct(private array $rule, private bool $langInUrl)
	{
		
	}

	public function create(): Route
	{
		if (isset($this->rule['handler'])) {
			$this->rule['controller'] =  $this->rule['handler'];
		}
		if (!isset($this->rule['controller'])) {
			throw new Exception("rule doesn't have any controller: " . print_r($this->rule, true));
		}
		if (isset($this->rule['middleware'])) {
			throw new Exception("middlewares are not supported, yet");
		}
		if (isset($this->rule['domain'])) {
			throw new Exception("domains are not supported, yet");
		}
		if (isset($this->rule['regex'])) {
			throw new Exception("regexs are not supported, yet");
		}
		if (isset($this->rule['scheme'])) {
			throw new Exception("schemes are not supported, yet");
		}

		$action = str_replace("/", "\\", $this->rule['controller']);
		$methods = isset($this->rule['method']) ?  array_map('strtoupper', (array)$this->rule['method']) : Router::$verbs;
		$uri = $this->buildUri();
		$isHomePage = ($uri == "");
		if ((!isset($this->rule['absolute']) or !$this->rule['absolute']) and $this->langInUrl) {
			$uri = ($isHomePage ? "{lang?}" : "{lang}") . "/{$uri}";
			$this->wheres['lang'] = "[a-z]{2}";
		}

		$route = new Route($methods, $uri, $action);
		foreach ($this->wheres as $parameter => $condition) {
			if (is_array($condition)) {
				$route->whereIn($parameter, $condition);
			} else {
				$route->where($parameter, $condition);
			}
		}

		return $route;
	}

	private function buildUri(): string {
		if (is_string($this->rule['path'])) {
			$this->rule['path'] = explode('/', $this->rule['path']);
		} elseif (!is_array($this->rule['path'])) {
			throw new Exception('path must be string or array: ' . print_r($this->rule['path'], true));
		}
		$uri = "";
		foreach ($this->rule['path'] as  $part) {
			if (!$part) {
				continue;
			}
			if ($uri) {
				$uri .= "/";
			}
			$part = self::validPart($part);
			if ('static' == $part['type']) {
				$uri .= $part['name'];
			} elseif ('dynamic' == $part['type']) {
				$uri .= "{{$part['name']}}";
				if (isset($part['regex'])) {
					if (str_starts_with($part['regex'], "/") and str_ends_with($part['regex'], "/")) {
						$part['regex'] = substr($part['regex'], 1, -1);
					}
					if (str_starts_with($part['regex'], "^") and str_ends_with($part['regex'], "$")) {
						$part['regex'] = substr($part['regex'], 1, -1);
					}
					$this->wheres[$part['name']] = $part['regex'];
				} elseif (isset($part['values'])) {
					if (!is_array($part['values']) or empty($part['values'])) {
						throw new RulePartValue($part);
					}
					foreach ($part['values'] as $value) {
						if (!is_string($value) and !is_numeric($value)) {
							throw new RulePartValue($part);
						}
					}
					$this->wheres[$part['name']] = $part['values'];
				}
			} elseif ('wildcard' == $part['type']) {
				$uri .= "{{$part['name']}}";
			}
		}

		return $uri;
	}

	private function validPart($part): array
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
			throw new RouterRulePart($part, "invalid route part");
		}
		if (!isset($part['name'])) {
			throw new RouterRulePart($part, "missing route part name");
		}
		if (!isset($part['type']) and (isset($part['regex']) or isset($part['values']))) {
			$part['type'] = 'dynamic';
		}
		if (!isset($part['type'])) {
			throw new RouterRulePart($part, 'type is unassigned');
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
							throw new RouterRulePart($part, "value is invalid");
						}
					}
				} else {
					throw new RouterRulePart($part, "values is not an array");
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
}
