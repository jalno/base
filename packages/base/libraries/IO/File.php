<?php

namespace packages\base\IO;

abstract class File extends Node
{
    /**
     * @param callable(File\Local):mixed $callback
     */
    public static function insureLocal(File $file, $callback = null): File\Local
    {
        if ($file instanceof File\Local) {
            $localFile = $file;
        } else {
            $localFile = new File\TMP();
            if ($file->exists()) {
                $file->copyTo($localFile);
            }
        }
        $originalMd5 = $file->exists() ? $localFile->md5() : null;
        if (null !== $callback) {
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

    abstract public function copyTo(File $dest): bool;

    abstract public function move(File $dest): bool;

    abstract public function read(int $length = 0): string;

    abstract public function write(string $data): bool;

    abstract public function size(): int;

    abstract public function __serialize(): array;

    abstract public function __unserialize(array $data): void;

    public function copyFrom(File $source): bool
    {
        return $source->copyTo($this);
    }

    public function getExtension(): string
    {
        $dot = strrpos($this->basename, '.');
        if (false === $dot) {
            return '';
        }

        return substr($this->basename, $dot + 1);
    }

    public function isEmpty(): bool
    {
        return 0 == $this->size();
    }
}
