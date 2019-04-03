<?php
namespace packages\base\Image;

use packages\base\Exception;

class UnsupportedFormatException extends Exception {
	/** @var string */
	protected $format;

	public function __constrcut(string $format, string $message = "") {
		$this->format = $fotmat;
		if (!$message) {
			$message = "{$format} is an unsupported image format";
		}
		parent::__constrcut($message);
	}

	public function getFormat(): string {
		return $this->format;
	}
}
