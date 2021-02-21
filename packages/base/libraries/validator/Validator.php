<?php
namespace packages\base;

class Validator {
	/**
	 * Add a validator.
	 * Do nothing if duplicate validator passed.
	 * 
	 * @param packages\base\Validator\IValidator|string validator object or validator class name
	 * @throws packages\base\Exception if found duplicate alias
	 * @throws TypeError if provided parameter was not string or an IValidator instance.
	 * @return void
	 */
	public static function addValidator($validator): void {
		if (empty(self::$validators)) {
			self::addDefaultValidators();
		}
		$classname = null;
		if (is_string($validator)) {
			$classname = $validator;
			$validator = new $validator();
		}
		if (!($validator instanceof Validator\IValidator)){
			throw new \TypeError("argument 1 is not string nor an IValidator instance");
		}
		if (!$classname) {
			$classname = get_class($validator);
		}
		if (isset(self::$validators[$classname])) {
			return;
		}
		$aliases = $validator->getTypes();
		foreach ($aliases as $alias) {
			if (in_array($alias, self::$aliases)) {
				throw new Exception("{$alias} alias is duplicate");
			}
			self::$aliases[$alias] = $classname;
		}
		self::$validators[$classname] = $validator;
	}
	/** @var array keys are class name and values are IValidator objects */
	private static $validators = [];

	/** @var array keys are string keys and values are class name */
	private static $aliases = [];

	/**
	 * Add some default validators.
	 * 
	 * @return void
	 */
	private static function addDefaultValidators(): void {
		$classes = [
			Validator\BooleanValidator::class,
			Validator\PhoneValidator::class,
			Validator\CellphoneValidator::class,
			Validator\EmailValidator::class,
			Validator\IPValidator::class,
			Validator\NumberValidator::class,
			Validator\StringValidator::class,
			Validator\URLValidator::class,
			Validator\FileValidator::class,
			Validator\ImageValidator::class,
			Validator\DateValidator::class,
		];
		foreach ($classes as $classname) {
			$validator = new $classname();
			$aliases = $validator->getTypes();
			foreach ($aliases as $alias) {
				self::$aliases[$alias] = $classname;
			}
			self::$validators[$classname] = $validator;
		}
	}
	protected $rules;
	protected $data;
	protected $newData = [];
	public function __construct(array $rules, array $data) {
		if (empty(self::$validators)) {
			self::addDefaultValidators();
		}
		$this->rules = $rules;
		$this->data = $data;
	}
	public function validate(): array {
		foreach ($this->rules as $input => $rule) {
			if (!isset($this->data[$input])) {
				if (!isset($rule['optional']) or !$rule['optional']) {
					throw new InputValidationException($input);
				}
				if(isset($rule['default'])) {
					$this->newData[$input] = $rule['default'];
				}
				continue;
			}
			if (is_string($this->data[$input]) and $this->data[$input] === "" and !isset($rule['empty']) and isset($rule['optional']) and $rule['optional']) {
				continue;
			}
			if (!isset($rule['type'])) {
				$this->newData[$input] = $this->data[$input];
				continue;
			}
			if (!is_array($rule['type'])) {
				$rule['type'] = array($rule['type']);
			}
			for ($x = 0, $l = count($rule['type']); $x < $l; $x++) {
				$isLast = $l - $x == 1;
				if ($this->validateInput($input, $rule, $rule['type'][$x], !$isLast)) {
					break;
				}
			}
		}
		return $this->newData;
	}

	/**
	 * validate a single input with specific type.
	 * 
	 * @param string $input input name
	 * @param array $rule
	 * @param packags\base\Validator\IValidator|Closure|string $type validator object or validator class name or validator alias
	 * @param bool $doNotPassValidationException
	 * @throws packages\base\Exception if $type argument is not IValidator instance
	 * @throws packages\base\InputValidation if validation failed and value of $doNotPassValidationException was false
	 */
	private function validateInput(string $input, array $rule, $type, bool $doNotPassValidationException): bool {
		$validator = null;
		if (is_string($type)) {
			if (isset(self::$aliases[$type])) {
				$validator = self::$validators[self::$aliases[$type]];
			} elseif (is_subclass_of($type, Validator\IValidator::class, true)) {
				$validator = new $type();
			} else {
				throw new Exception("{$type} is unkown type");
			}
		} elseif (is_a($type, Validator\IValidator::class) or is_a($type, \Closure::class)) {
			$validator = $type;
		} else {
			throw new Exception("{$type} is not implementing " . Validator\IValidator::class);
		}
		if ($doNotPassValidationException) {
			try {
				if ($type instanceof \Closure) {
					$newData = $type($this->data[$input], $rule, $input);
				} else {
					$rule['type'] = $type;
					$newData = $validator->validate($input, $rule, $this->data[$input]);
				}
			} catch (InputValidationException $e) {
				return false;
			}
		} else {
			if ($type instanceof \Closure) {
				$newData = $type($this->data[$input], $rule, $input);
			} else {
				$rule['type'] = $type;
				$newData = $validator->validate($input, $rule, $this->data[$input]);
			}
		}
		if (is_object($newData) and $newData instanceof Validator\NullValue) {
			$this->newData[$input] = null;
		} else {
			$this->newData[$input] = $newData ?? $this->data[$input];
		}
		return true;
	}
}
