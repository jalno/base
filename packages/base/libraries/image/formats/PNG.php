<?php
namespace packages\base\Image;

use packages\base\IO\File;

class PNG extends GD {

	/**
	 * Save the image to a file.
	 * 
	 * @param packages\base\IO\File $file
	 * @param int $quality
	 * @return void
	 */
	public function saveToFile(file $file, int $quality = 75): void {
		imagepng($this->image, $file->getPath(), round((100 - $quality) / 10));
	}

	/**
	 * Get format of current image.
	 * 
	 * @return string
	 */
	public function getExtension(): string {
		return 'png';
	}

	/**
	 * Create new image with provided background color
	 * 
	 * @param int $width
	 * @param int $height
	 * @param packages\base\Image\Color $bg
	 * @return void
	 */
	protected function createBlank(int $width, int $height, Color $bg): void {
		$this->image = imagecreatetruecolor($width, $height);
		$colors = $bg->toRGBA();
		$colors[3] = round(127 - ($colors[3] * 127));
		$rgba = imagecolorallocatealpha($this->image, $colors[0], $colors[1], $colors[2], $colors[3]);
		imagecolortransparent($this->image, $rgba);
		imageAlphaBlending($this->image, false);
		imageSaveAlpha($this->image, true);

		imagefilledrectangle($this->image, 0, 0, $width, $height, $rgba);
	}

	/**
	 * Read the image from constructor file.
	 * 
	 * @throws InvalidImageFileException if gd library was unable to load a png image from the file.
	 * @return void
	 */
	protected function fromFile(): void {
		$this->image = imagecreatefrompng($this->file->getPath());
		if (!is_resource($this->image)) {
			throw new InvalidImageFileException($this->file);
		}
	}
}