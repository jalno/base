<?php

namespace packages\base\Image;

use packages\base\IO\File;

class GIF extends GD
{
    /**
     * Save the image to a file.
     */
    public function saveToFile(File $file, int $quality = 75): void
    {
        File::insureLocal($file, function (File\Local $local) {
            imagegif($this->image, $local->getPath());
        });
    }

    /**
     * Get format of current image.
     */
    public function getExtension(): string
    {
        return 'gif';
    }

    /**
     * Read the image from constructor file.
     *
     * @throws InvalidImageFileException if gd library was unable to load a gif image from the file
     */
    protected function fromFile(): void
    {
        $local = File::insureLocal($this->file);
        $image = imagecreatefromgif($local->getPath());
        if (false === $image) {
            throw new InvalidImageFileException($local);
        }
        $this->image = $image;
    }
}
