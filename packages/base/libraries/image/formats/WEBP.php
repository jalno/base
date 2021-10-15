<?php
namespace packages\base\Image;

use packages\base\IO\File;

class WEBP extends GD {

	/**
	 * Save the image to a file.
	 */
	public function saveToFile(File $file, int $quality = 75): void {
		File::insureLocal($file, function(File\Local $local) use ($quality) {
			imagewebp($this->image, $local->getPath(), $quality);
		});
	}

	/**
	 * Get format of current image.
	 */
	public function getExtension(): string {
		return 'webp';
	}

	/**
	 * Read the image from constructor file.
	 * 
	 * @throws InvalidImageFileException if gd library was unable to load a webp image from the file.
	 */
	protected function fromFile(): void {
		$local = File::insureLocal($this->file);
		$image = imagecreatefromwebp($local->getPath());
		if ($image === false) {
			throw new InvalidImageFileException($local);
		}
		$this->image = $image;
	}
}
