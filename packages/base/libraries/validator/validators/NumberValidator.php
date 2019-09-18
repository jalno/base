<?php
namespace packages\base\Validator;

use packages\base\InputValidationException;

class NumberValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['number', 'int', 'int8', 'int16', 'int32', 'int64', 'uint', 'uint8', 'uint16', 'uint32', 'uint64', 'float'];
	}

	/**
	 * Validate data to be a number.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return mixed|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		if (!is_string($data) and !is_numeric($data)) {
			throw new InputValidationException($input);
		}
		switch ($rule['type']) {
			case("int8"):
				if (!isset($rule['min'])) $rule['min'] = -128;
				if (!isset($rule['max'])) $rule['max'] = 127;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = true;
				$rule['float'] = false;
				break;
			case("int16"):
				if (!isset($rule['min'])) $rule['min'] = -32768;
				if (!isset($rule['max'])) $rule['max'] = 32767;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = true;
				$rule['float'] = false;
				break;
			case("int"):
			case("int32"):
				if (!isset($rule['min'])) $rule['min'] = -2147483648;
				if (!isset($rule['max'])) $rule['max'] = 2147483647;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = true;
				$rule['float'] = false;
				break;
			case("int64"):
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = true;
				$rule['float'] = false;
				break;
			case("uint8"):
				if (!isset($rule['min'])) $rule['min'] = 0;
				if (!isset($rule['max'])) $rule['max'] = 255;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = false;
				$rule['float'] = false;
				break;
			case("uint16"):
				if (!isset($rule['min'])) $rule['min'] = 0;
				if (!isset($rule['max'])) $rule['max'] = 65535;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = false;
				$rule['float'] = false;
				break;
			case("uint"):
			case("uint32"):
				if (!isset($rule['min'])) $rule['min'] = 0;
				if (!isset($rule['max'])) $rule['max'] = 4294967295;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = false;
				$rule['float'] = false;
				break;
			case("uint64"):
				if (!isset($rule['min'])) $rule['min'] = 0;
				if (!isset($rule['zero'])) $rule['zero'] = true;
				$rule['negetive'] = false;
				$rule['float'] = false;
				break;
			case("float"):
				$rule['float'] = true;
				break;
		}
		$rule['float'] = $rule['float'] ?? false;
		$rule['zero'] = $rule['zero'] ?? $rule['empty'] ?? false;
		if (!$data) {
			if (!$rule['zero']) {
				throw new InputValidationException($input, "empty-value");
			}
			if (isset($rule['default'])) {
				return $rule['default'];
			}
			return;
		}
		$regexStr = "^\\s*";
		if (isset($rule['negetive']) and $rule['negetive']) {
			$regexStr .= "-?";
		}
		$regexStr .= "\\d+";
		if ($rule['float']) {
			$regexStr .= "(?:\\.\\d+)?";
		}
		$regexStr .= "\\s*$";
		if (!preg_match("/{$regexStr}/", $data)) {
			throw new InputValidationException($input, "not-a-number");
		}
		$number = $rule['float'] ? floatval($data) : intval($data);
		if (isset($rule['values']) and $rule['values']) {
			if (!in_array($data, $rule['values'])) {
				throw new InputValidationException($input, "not-defined-value");
			}
		} else {
			if (!$number and !$rule['zero']) {
				throw new InputValidationException($input);
			}
			if (isset($rule['min']) and $number < $rule['min']) {
				throw new InputValidationException($input, "min-value");
			}
			if (isset($rule['max']) and $number > $rule['max']) {
				throw new InputValidationException($input, "max-value");
			}
		}
		return $number;
	}
}
