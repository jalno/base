<?php
namespace packages\base\IO;
abstract class file implements \Serializable{
	public $directory;
	public $basename;
	public function __construct(string $path = ''){
		if ($path !== '') {
			$this->directory = dirname($path);
			$this->basename = basename($path);
		}
	}
	abstract public function copyTo(file $dest): bool;
	abstract public function delete();
	abstract public function rename(string $newName): bool;
	abstract public function move(file $dest):bool;
	abstract public function read(int $length = 0): string;
	abstract public function write(string $data): bool;
	abstract public function size(): int;
	abstract public function exists(): bool;
	abstract public function getDirectory();
	public function getPath(): string {
		if (
			($this->directory === null or $this->directory === '') and
			($this->basename === null or $this->basename === '')
		) {
			return '';
		}
		return $this->directory . '/' . $this->basename;
	}
	public function copyFrom(file $source): bool{
		return $source->copyTo($this);
	}
	public function getExtension(): string {
		$dot = strrpos($this->basename, '.');
		if ($dot === false) {
			return "";
		}
		return substr($this->basename, $dot + 1);
	}
	public function isEmpty():bool{
		return $this->size() == 0;
	}
}