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
		if (!is_array($data) or !isset(http::$files[$input])) {
			throw new InputValidationException($input);
		}
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
}
