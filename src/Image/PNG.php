<?php

namespace packages\base\Image;

use packages\base\IO\File;

class PNG extends GD
{
    /**
     * Save the image to a file.
     */
    public function saveToFile(File $file, int $quality = 75): void
    {
        File::insureLocal($file, function (File\Local $local) use ($quality) {
            imagepng($this->image, $local->getPath(), round((100 - $quality) / 10));
        });
    }

    /**
     * Get format of current image.
     */
    public function getExtension(): string
    {
        return 'png';
    }

    /**
     * Create new image with provided background color.
     */
    protected function createBlank(int $width, int $height, Color $bg): void
    {
        $this->image = imagecreatetruecolor($width, $height);
        $colors = $bg->toRGBA();
        $colors[3] = round(127 - ($colors[3] * 127));
        $rgba = imagecolorallocatealpha($this->image, $colors[0], $colors[1], $colors[2], $colors[3]);
        imagecolortransparent($this->image, $rgba);
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        imagefilledrectangle($this->image, 0, 0, $width, $height, $rgba);
    }

    /**
     * Read the image from constructor file.
     *
     * @throws InvalidImageFileException if gd library was unable to load a png image from the file
     */
    protected function fromFile(): void
    {
        $local = File::insureLocal($this->file);
        $image = imagecreatefrompng($local->getPath());
        if (false === $image) {
            throw new InvalidImageFileException($local);
        }
        $this->image = $image;
    }
}
