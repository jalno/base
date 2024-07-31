<?php

namespace packages\base\Routing;

use Illuminate\Routing\ControllerDispatcher as LaravelControllerDispatcher;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

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
}
