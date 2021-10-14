<?php
namespace packages\base\response;

use packages\base\{IO, IO\Buffer, IO\IStreamableFile, Exception};

class File {

	protected const OUTPUT_CHUNK_SIZE = 8192;

	/** @var \packages\base\IO\buffer|null */
	private $stream;

	/** @var \packages\base\IO\File|null */
	private $location;

	/** @var string */
	private $mimeType;

	/** @var int|null */
	private $size;

	/** @var string|null */
	private $name;

	/**
	 * Set a resource for response.
	 * 
	 * @param packages\base\IO\buffer|resource|null $stream
	 * @throws packages\base\Exception if passed argument was not resource nor null
	 * @return void
	 */
	public function setStream($stream): void {
		if (is_resource($stream)) {
			$stream = new Buffer($stream);
		}
		if (!is_null($stream) and !($stream instanceof Buffer)) {
			throw new Exception("argument 1 passed must be a resource or " . Buffer::class . " instance");
		}
		$this->stream = $stream;
	}


	/**
	 * Get setted buffer or new read-only buffer of file.
	 * 
	 * @return packages\base\IO\Buffer|null
	 */
	public function getStream(): ?Buffer {
		if ($this->stream) {
			return $this->stream;
		}
		if ($this->location) {
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
	 * Set a file location or file object for response
	 * 
	 * @param packages\base\IO\File|string|null $location
	 * @throws packages\base\Exception for unkonwn arguments
	 * @throws packages\base\IO\NotFoundException if file wasn't exist.
	 * @return void
	 */
	public function setLocation($location): void {
		if (is_string($location)) {
			$location = new IO\File\Local($location);
		}
		if (!is_null($location) and !($location instanceof IO\File)) {
			throw new Exception("argument 1 passed must be a string or " . IO\File::class . " instance");
		}
		if (!is_null($location)) {
			if (!$location->exists()) {
				throw new IO\NotFoundException($location);
			}
		}
		$this->location = $location;
	}

	/**
	 * Getter for file object
	 * 
	 * @return packages\base\IO\File|null
	 */
	public function getLocation(): ?IO\File {
		return $this->location;
	}

	/**
	 * Set original size of stream or file
	 * 
	 * @param int|null $size
	 * @return void
	 */
	public function setSize(?int $size): void {
		$this->size = $size;
	}

	/**
	 * Get setted size or calculated size of file
	 * 
	 * @return int|null
	 */
	public function getSize(): ?int {
		if ($this->size) {
			return $this->size;
		}
		if ($this->location) {
			return $this->location->size();
		}
		return null;
	}

	/**
	 * Set original name of stream or file
	 * 
	 * @param string|null $name
	 * @return void
	 */
	public function setName(?string $name): void {
		$this->name = $name;
	}

	/**
	 * Get setted name or original name of file
	 * 
	 * @return string|null
	 */
	public function getName(): ?string {
		if ($this->name) {
			return $this->name;
		}
		if ($this->location) {
			return $this->location->basename;
		}
		return null;
	}

	/**
	 * Set mime-type of stream or file
	 * 
	 * @param string $type
	 * @return void
	 */
	public function setMimeType(string $type): void {
		$this->mimeType = $type;
	}

	/**
	 * Get setted mime-type or dettected type of name
	 */
	public function getMimeType(): ?string {
		if ($this->mimeType) {
			return $this->mimeType;
		}
		if ($this->getName()) {
			return IO\mime_type($this->getName());
		}
		return null;
	}

	/**
	 * Echo output of stream or file.
	 * 
	 * @return void
	 */
	public function output(): void {
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
