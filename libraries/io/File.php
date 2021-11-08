<?php
namespace packages\base\IO;
abstract class File extends Node implements \Serializable {
	/**
	 * @param callable(File\Local):mixed $callback
	 */
	public static function insureLocal(File $file, $callback = null): File\Local {
		if ($file instanceof File\Local) {
			$localFile = $file;
		} else {
			$localFile = new File\Tmp();
			if ($file->exists()) {
				$file->copyTo($localFile);
			}
		}
		$originalMd5 = $file->exists() ? $localFile->md5() : null;
		if ($callback !== null) {
			call_user_func($callback, $localFile);
			if ($localFile !== $file) {
				if ($localFile->exists()) {
					if ($originalMd5 !== $localFile->md5()) {
						$localFile->copyTo($file);
					}
				} else {
					$file->delete();
				}
			}
		}
		
		return $localFile;
	}

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