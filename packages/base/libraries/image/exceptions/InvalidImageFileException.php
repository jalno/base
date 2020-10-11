<?php
namespace packages\base\Image;

use packages\base\{Exception, IO\File};

class InvalidImageFileException extends Exception {

	/** @var package\base\IO\File is the invalid image file */
	protected $file;

	public function __construct(File $file, string $message = "") {
		$this->file = $file;
		if (!$message) {
			$message = "{$file->getPath()} is an invalid image";
		}
		parent::__construct($message);
	}

	public function getInvalidFile(): File {
		return $this->file;
	}
}
