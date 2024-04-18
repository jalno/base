<?php

namespace packages\base\Response;

use packages\base\Exception;
use packages\base\IO;
use packages\base\IO\Buffer;
use packages\base\IO\IStreamableFile;

class File
{
    protected const OUTPUT_CHUNK_SIZE = 8192;

    private ?Buffer $stream = null;

    private ?IO\File $location = null;

    private ?string $mimeType = null;
    private int $size = 0;
    private ?string $name = null;

    /**
     * Set a resource for response.
     *
     * @param Buffer|resource|null $stream
     *
     * @throws Exception if passed argument was not resource nor null
     */
    public function setStream($stream): void
    {
        if (is_resource($stream)) {
            $stream = new Buffer($stream);
        }
        if (!is_null($stream) and !($stream instanceof Buffer)) {
            throw new Exception('argument 1 passed must be a resource or '.Buffer::class.' instance');
        }
        $this->stream = $stream;
    }

    /**
     * Get setted buffer or new read-only buffer of file.
     */
    public function getStream(): ?Buffer
    {
        if (!$this->stream and $this->location) {
            if ($this->location instanceof IStreamableFile) {
                $this->stream = $this->location->open('r');
            } else {
                $tmp = new IO\File\TMP();
                if (!$this->location->copyTo($tmp)) {
                    throw new IO\ReadException($this->location);
                }
                $this->stream = $tmp->open('r');
            }
        }

        return $this->stream;
    }

    /**
     * Set a file location or file object for response.
     *
     * @param IO\File|string|null $location
     *
     * @throws Exception            for unkonwn arguments
     * @throws IO\NotFoundException if file wasn't exist
     */
    public function setLocation($location): void
    {
        if (is_string($location)) {
            $location = new IO\File\Local($location);
        }
        if (!is_null($location) and !($location instanceof IO\File)) {
            throw new Exception('argument 1 passed must be a string or '.IO\File::class.' instance');
        }
        if (!is_null($location)) {
            if (!$location->exists()) {
                throw new IO\NotFoundException($location);
            }
        }
        $this->location = $location;
    }

    /**
     * Getter for file object.
     */
    public function getLocation(): ?IO\File
    {
        return $this->location;
    }

    /**
     * Set original size of stream or file.
     */
    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    /**
     * Get setted size or calculated size of file.
     */
    public function getSize(): int
    {
        if (!$this->size and $this->location) {
            $this->size = $this->location->size();
        }

        return $this->size;
    }

    /**
     * Set original name of stream or file.
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get setted name or original name of file.
     */
    public function getName(): ?string
    {
        if (!$this->name and $this->location) {
            $this->name = $this->location->basename;
        }

        return $this->name;
    }

    /**
     * Set mime-type of stream or file.
     */
    public function setMimeType(string $type): void
    {
        $this->mimeType = $type;
    }

    /**
     * Get setted mime-type or dettected type of name.
     */
    public function getMimeType(): ?string
    {
        if (!$this->mimeType and $this->getName()) {
            $this->mimeType = IO\mime_type($this->getName());
        }

        return $this->mimeType;
    }

    /**
     * Echo output of stream or file.
     */
    public function output(): void
    {
        $stream = $this->getStream();
        if ($stream) {
            $size = $this->getSize();
            $read = 0;
            $lastRead = self::OUTPUT_CHUNK_SIZE;
            while ((!$size and $lastRead == $lastRead) or ($size and $read < $size)) {
                $chunk = $stream->read(self::OUTPUT_CHUNK_SIZE);
                $lastRead = strlen($chunk);
                $read += $lastRead;
                echo $chunk;
            }
        }
    }
}
