<?php

namespace packages\base\Routing;

use Illuminate\Routing\ControllerDispatcher as LaravelControllerDispatcher;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Request;
use packages\base\DB\DuplicateRecord;
use packages\base\InputValidationException;
use packages\base\View\Error;
use packages\base\Views\Form;
use packages\base\Views\FormError;

class ControllerDispatcher extends LaravelControllerDispatcher
{
	protected function isJalnoDataControllerParameter(ReflectionParameter $reflector): bool {
		if ($reflector->getPosition() !== 0) {
			return false;
		}
		if ($reflector->getName() != 'data') {
			return false;
		}
		$type = $reflector->getType();
		return (!$type or ($type instanceof ReflectionNamedType and $type->getName() === 'array'));
	}

	public function resolveMethodDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
	{
		$methodParameters = $reflector->getParameters();
		if (count($methodParameters) == 1) {
			if ($this->isJalnoDataControllerParameter($p = $methodParameters[0])) {
				return [$parameters];
			}
		}
		return parent::resolveMethodDependencies($parameters, $reflector);
	}

	public function dispatch(Route $route, $controller, $method)
	{
		try {
			return parent::dispatch($route, $controller, $method);
		} catch (InputValidationException | DuplicateRecord | Error $e) {
			$response = $controller->getResponse();
			if (!$response) {
				throw $e;
			}
			$response->setStatus(false);

			if (!($e instanceof Error)) {
				$e = FormError::fromException($e);
			}

			$view = $response->getView();
			if ($view) {
				if ($view instanceof Form) {
					$view->setFormError($e);
					$view->setDataForm(Request::instance()->request->all());
				}
			} else {
				$e->setTraceMode(Error::NO_TRACE);
				$response->setData([
					'error' => [$e],
				]);
			}

			return $response;
		}
	}
}
