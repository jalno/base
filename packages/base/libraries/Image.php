<?php

namespace packages\base;

use packages\base\IO\File;
use packages\base\IO\NotFoundException;

abstract class Image
{
    /**
     * identify and construct an image from its file extension.
     *
     * @throws Image\UnsupportedFormatException if the format was not supported
     * @throws Image\InvalidImageFileException  if file content was corrupted or was incompatible to its extension
     */
    public static function fromFormat(File $file): Image
    {
        switch (strtolower($file->getExtension())) {
            case 'jpeg':
            case 'jpg':
                return new Image\JPEG($file);
            case 'png':
                return new Image\PNG($file);
            case 'gif':
                return new Image\GIF($file);
            case 'webp':
                return new Image\WEBP($file);
            default:
                throw new Image\UnsupportedFormatException($file->getExtension());
        }
    }

    /**
     * identify and construct an image from its file content.
     *
     * @throws Image\UnsupportedFormatException if the format was not supported
     */
    public static function fromContent(File $file): Image
    {
        $localFile = File::insureLocal($file);
        $info = @getimagesize($localFile->getPath());
        if (!$info) {
            throw new Image\UnsupportedFormatException('');
        }
        $image = null;
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $image = new Image\JPEG($localFile);
                break;
            case IMAGETYPE_PNG:
                $image = new Image\PNG($localFile);
                break;
            case IMAGETYPE_GIF:
                $image = new Image\GIF($localFile);
                break;
            case IMAGETYPE_WEBP:
                $image = new Image\WEBP($localFile);
                break;
            default:
                throw new Image\UnsupportedFormatException($info[2]);
        }

        // It's kind of messy but it's because we want to getFile() always return the truth not a tmp file.
        // And besides that our tmp file have no extension and that may cause a problem.
        // Also we could send $file to constructors but it's cause double downloading of remote files.
        $image->file = $file;

        return $image;
    }

    /** @var File constructor file */
    protected $file;

    /**
     * Construct an image object with three ways:
     * 	1. pass a file to {$param}
     * 	2. pass other image to {$param}
     * 	3. pass new image width to {$param}.
     *
     * @param File|Image|int
     * @param int|null    $height height of new image in third method
     * @param Image\Color $bg     background color of new image in third method
     *
     * @throws NotFoundException                if passed file cannot be found
     * @throws Images\InvalidImageFileException if file content was corrupted
     */
    public function __construct($param = null, ?int $height = null, ?Image\Color $bg = null)
    {
        if ($param instanceof File) {
            if (!$param->exists()) {
                throw new NotFoundException($param);
            }
            $this->file = $param;
            $this->fromFile();
        } elseif ($param instanceof self) {
            $this->fromImage($param);
        } else {
            $this->createBlank($param, $height, $bg);
        }
    }

    /**
     * If image was constructed by a file, this method will return the file.
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Save the iamge by overwriting constructor file.
     */
    public function save(int $quality = 75): void
    {
        $this->saveToFile($this->file, $quality);
    }

    /**
     * Resize the image to height.
     * Width will scaled based on height.
     *
     * @param int $height new height in px
     *
     * @return Image resized image
     */
    public function resizeToHeight(int $height): Image
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;

        return $this->resize($width, $height);
    }

    /**
     * Resize the image to width.
     * Height will scaled based on width.
     *
     * @param int $width new width in px
     *
     * @return Image resized image
     */
    public function resizeToWidth(int $width): Image
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;

        return $this->resize($width, $height);
    }

    public function scale(int $scale): Image
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;

        return $this->resize($width, $height);
    }

    /**
     * Get color of specified pixel.
     */
    abstract public function colorAt(int $x, int $y): Image\Color;

    /**
     * Set color of specified pixel.
     */
    abstract public function setColorAt(int $x, int $y, Image\Color $color): void;

    /**
     * Resize the image to new width and height.
     *
     * @param int $width  in px
     * @param int $height in px
     *
     * @return Image resized image
     */
    abstract public function resize(int $width, int $height): Image;

    /**
     * Get width of current image.
     *
     * @return int in px
     */
    abstract public function getWidth(): int;

    /**
     * Get height of current image.
     *
     * @return int in px
     */
    abstract public function getHeight(): int;

    /**
     * Get format of current image.
     */
    abstract public function getExtension(): string;

    /**
     * Put anthor image on current image.
     *
     * @param int $x x-coordinate of destination point
     * @param int $y y-coordinate of destination point
     */
    abstract public function paste(Image $image, int $x, int $y): void;

    /**
     * Copy a part of image starting at the x,y coordinates with a width and height.
     *
     * @param int $x      x-coordinate of point
     * @param int $y      y-coordinate of point
     * @param int $height
     */
    abstract public function copy(int $x, int $y, int $width, $height): Image;

    /**
     * Rotate an image with a given angle
     * The center of rotation is the center of the image, and the rotated image may have different dimensions than the original image.
     *
     * @param float       $angle Rotation angle, in degrees. The rotation angle is interpreted as the number of degrees to rotate the image anticlockwise.
     * @param Image\Color $bg    specifies the color of the uncovered zone after the rotation
     *
     * @return Image Rotated image
     */
    abstract public function rotate(float $angle, Image\Color $bg): Image;

    /**
     * Save the image to a file.
     */
    abstract public function saveToFile(File $file, int $quality = 75): void;

    /**
     * Copy anthor image to current image;.
     *
     * @param Image $other source image
     */
    protected function fromImage(Image $other): void
    {
        $width = $other->getWidth();
        $height = $other->getHeight();
        $bg = Image\Color::fromRGBA(0, 0, 0, 0);
        $this->createBlank($width, $height, $bg);
        for ($x = 0; $x < $width; ++$x) {
            for ($y = 0; $y < $height; ++$y) {
                $color = $other->colorAt($x, $y);
                $this->setColorAt($x, $y, $color);
            }
        }
    }

    /**
     * Read the image from constructor file.
     *
     * @throws Image\InvalidImageFileException if the format was not supported
     */
    abstract protected function fromFile(): void;

    /**
     * Create new image with provided background color.
     *
     * @return void
     */
    abstract protected function createBlank(int $width, int $height, Image\Color $bg);
}
