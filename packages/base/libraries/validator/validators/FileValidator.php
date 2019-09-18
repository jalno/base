<?php
namespace packages\base\Validator;

use packages\base\{http, IO\file, InputValidationException};

class FileValidator implements IValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['file'];
	}

	/**
	 * Validate data to be a email.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return packages\base\IO\file\local|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		// To prevent user send a $_FILE-like field using post or get data, we will check http::$files directly.
		if (!is_array($data) or ((!isset($rule['prevent-reality-check']) or !$rule['prevent-reality-check']) and !isset(http::$files[$input]))) {
			throw new InputValidationException($input);
		}
		if (!isset($data['error'])) {
			if (!isset($rule['multiple']) or !$rule['multiple']) {
				throw new InputValidationException($input);
			}
			$files = null;
			$x = 0;
			foreach ($data as $file) {
				$result = $this->validateSingleFile($input . "[{$x}]", $rule, $file);
				if ($result) {
					if (is_object($result) and $result instanceof NullValue) {
						return $result;
					}
					if ($files === null) {
						$files = [];
					}
					$files[] = $result;
				} else {
					$files[] = $file;
				}
				$x++;
			}
			return $files;
		}
		return $this->validateSingleFile($input, $rule, $data);
	}

	protected function validateSingleFile(string $input, array $rule, $data) {
		if ($data['error'] == UPLOAD_ERR_NO_FILE) {
			if (!isset($rule['optional']) or !$rule['optional']) {
				throw new InputValidationException($input);
			}
			if (isset($rule['default'])) {
				return $rule['default'];
			}
			return new NullValue();
		}
		if ($data['error'] != UPLOAD_ERR_OK) {
			throw new InputValidationException($input, "file error: {$data['error']}");
		}
		if (isset($rule['extension']) and $rule['extension']) {
			if (!is_array($rule['extension'])) {
				$rule['extension'] = array($rule['extension']);
			}
			$extension = substr($data['name'], strrpos($data['name'], '.')+1);
			if (!in_array($extension, $rule['extension'])) {
				throw new InputValidationException($input, "extension");
			}
		}
		if (isset($rule['min-size']) and $rule['min-size'] > 0 and $data['size'] < $rule['min-size']) {
			throw new InputValidationException($input, "min-size");
		}
		if (isset($rule['max-size']) and $rule['max-size'] > 0 and $data['size'] > $rule['max-size']) {
			throw new InputValidationException($input, "max-size");
		}
		if (isset($rule['obj']) and $rule['obj']) {
			return new file\local($data['tmp_name']);
		}
	}
	private function diverseArray(array $vector): array {
		$result = array();
		foreach ($vector as $key1 => $value1) {
			foreach ($value1 as $key2 => $value2) {
				$result[$key2][$key1] = $value2;
			}
		}
		return $result;
	}
}
