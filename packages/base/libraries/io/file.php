<?php
namespace packages\base\IO;
abstract class File extends Node implements \Serializable {
	abstract public function copyTo(file $dest): bool;
	abstract public function move(file $dest):bool;
	abstract public function read(int $length = 0): string;
	abstract public function write(string $data): bool;
	abstract public function size(): int;
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