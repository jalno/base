<?php
namespace packages\base\Image;

use packages\base\Exception;

class UnsupportedFormatException extends Exception {
	/** @var string */
	protected $format;

	public function __construct(string $format, string $message = "") {
		$this->format = $format;
		if (!$message) {
			$message = "{$format} is an unsupported image format";
		}
		parent::__construct($message);
	}

	public function getFormat(): string {
		return $this->format;
	}
}
