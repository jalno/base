<?php
namespace packages\base\Validator;

use packages\base\{Image, InputValidationException};

class ImageValidator extends FileValidator {
	/**
	 * Get alias types
	 * 
	 * @return string[]
	 */
	public function getTypes(): array {
		return ['image'];
	}

	/**
	 * Validate data to be a email.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return packages\base\IO\image|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		if (isset($data['error'])) {
			$this->checkAndFixExtensionByMime($data);
		} else {
			foreach ($data as $item) {
				$this->checkAndFixExtensionByMime($item);
			}
		}
		if (!isset($rule['extension'])) {
			$rule['extension'] = ['jpeg', 'jpg', 'png', 'gif'];
		}
		$file = parent::validate($input, array_replace($rule, ['obj' => true]), $data);
		if (!$file or $file instanceof NullValue) {
			return $file;
		}
		try {
			$image = Image::fromContent($file);
		} catch (Image\UnsupportedFormatException $e) {
			throw new InputValidationException($input, "unsupported-format");
		}

		if (isset($input['max-size'])) {
			$input['max-width'] = $input['max-size'][0];
			$input['max-height'] = $input['max-size'][1];
		}
		if (isset($input['min-size'])) {
			$input['min-width'] = $input['min-size'][0];
			$input['min-height'] = $input['min-size'][1];
		}
		if (isset($input['min-width']) and $input['min-width'] > 0 and $image->getWidth() < $input['min-width']) {
			throw new InputValidationException($input, "min-width: {$input['min-width']}px");
		}
		if (isset($input['max-width']) and $input['max-width'] > 0 and $image->getWidth() > $input['max-width']) {
			throw new InputValidationException($input, "max-width: {$input['max-width']}px");
		}
		if (isset($input['min-height']) and $input['min-height'] > 0 and $image->getHeight() < $input['min-height']) {
			throw new InputValidationException($input, "min-height: {$input['min-height']}px");
		}
		if (isset($input['max-height']) and $input['max-height'] > 0 and $image->getHeight() > $input['max-height']) {
			throw new InputValidationException($input, "max-height: {$input['max-height']}px");
		}
		if (isset($input['resize'])) {
			$input['resize-width'] = $input['resize'][0];
			$input['resize-height'] = $input['resize'][1];
		}
		if (isset($input['resize-width']) and $input['resize-width'] > 0) {
			$image = $image->resize($input['resize-width'], $image->getHeight());
		}
		if (isset($input['resize-height']) and $input['resize-height'] > 0) {
			$image = $image->resize($image->getWidth(), $input['resize-height']);
		}
		if (!isset($rule['obj']) or $rule['obj']) {
			return $image;
		}
	}

	/**
	 * Check given file extension and mime is equal to real file extension and mime
	 * and correct it if not equal
	 *
	 * @param array $file that is array should contain "name", "tmp_name", "type" indexes
	 */
	private function checkAndFixExtensionByMime(array &$file): void {
		$lastDot = strrpos($file['name'], '.');
		$extension = ($lastDot === false ? '' : substr($file['name'], $lastDot + 1));

		$mime = mime_content_type($file['tmp_name']);
		$realExtension = substr($mime, strrpos($mime, '/') + 1);

		if ($file['type'] != $mime) {
			$file['type'] = $mime;
		}
		if ($extension != $realExtension) {
			$file['name'] .= '.' . strtolower($realExtension);
		}
	}
}
