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
	 * Validate data to be a image.
	 * 
	 * @throws packages\base\InputValidationException
	 * @param string $input
	 * @param array $rule
	 * @param mixed $data
	 * @return packages\base\IO\image|null new value, if needed.
	 */
	public function validate(string $input, array $rule, $data) {
		if (isset($data['error'])) {
			if ($data['error'] == UPLOAD_ERR_OK) {
				$this->checkAndFixExtensionByMime($data);
			}
		} else {
			foreach ($data as $key => $item) {
				if ($item['error'] == UPLOAD_ERR_OK) {
					$this->checkAndFixExtensionByMime($item);
				}
			}
		}
		if (!isset($rule['extension'])) {
			$rule['extension'] = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
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

		if (isset($rule['min-size'])) {
			$rule['min-width'] = $rule['min-size'][0];
			$rule['min-height'] = $rule['min-size'][1];
		}
		if (isset($rule['max-size'])) {
			$rule['max-width'] = $rule['max-size'][0];
			$rule['max-height'] = $rule['max-size'][1];
		}
		if (isset($rule['min-width']) and $rule['min-width'] > 0 and $image->getWidth() < $rule['min-width']) {
			throw new InputValidationException($input, "min-width: {$rule['min-width']}px");
		}
		if (isset($rule['max-width']) and $rule['max-width'] > 0 and $image->getWidth() > $rule['max-width']) {
			throw new InputValidationException($input, "max-width: {$rule['max-width']}px");
		}
		if (isset($rule['min-height']) and $rule['min-height'] > 0 and $image->getHeight() < $rule['min-height']) {
			throw new InputValidationException($input, "min-height: {$rule['min-height']}px");
		}
		if (isset($rule['max-height']) and $rule['max-height'] > 0 and $image->getHeight() > $rule['max-height']) {
			throw new InputValidationException($input, "max-height: {$rule['max-height']}px");
		}
		if (isset($rule['resize'])) {
			$rule['resize-width'] = $rule['resize'][0];
			$rule['resize-height'] = $rule['resize'][1];
		}

		$shouldResizeWidth = (isset($rule['resize-width']) and $rule['resize-width'] > 0);
		$shouldResizeHeight = (isset($rule['resize-height']) and $rule['resize-height'] > 0);

		if ($shouldResizeWidth and $shouldResizeHeight) {
			$image = $image->resize($rule['resize-width'], $rule['resize-height']);
		} else if ($shouldResizeWidth) {
			$image = $image->resize($rule['resize-width'], $image->getHeight());
		} else if ($shouldResizeHeight) {
			$image = $image->resize($image->getWidth(), $rule['resize-height']);
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
