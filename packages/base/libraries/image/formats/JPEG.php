<?php
namespace packages\base\Image;

use packages\base\IO\File;

class JPEG extends GD {

	/**
	 * Save the image to a file.
	 */
	public function saveToFile(File $file, int $quality = 75): void {
		File::insureLocal($file, function(File\Local $local) use ($quality) {
			imagejpeg($this->image, $local->getPath(), $quality);
		});
		
	}

	/**
	 * Get format of current image.
	 */
	public function getExtension(): string {
		return 'jpg';
	}

	/**
	 * Read the image from constructor file.
	 * 
	 * @throws InvalidImageFileException if gd library was unable to load a jpeg image from the file.
	 */
	protected function fromFile(): void {
		$local = File::insureLocal($this->file);
		$image = imagecreatefromjpeg($local->getPath());
		if ($image === false) {
			throw new InvalidImageFileException($local);
		}
		$this->image = $image;
	}
}
