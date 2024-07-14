<?php

namespace packages\base\IO\File;

use packages\base\Exception;
use packages\base\IO\Buffer;
use packages\base\IO\Directory;
use packages\base\IO\File;
use packages\base\IO\IStreamableFile;

class Local extends File implements IStreamableFile
{
    public const readOnly = 'r';
    public const writeOnly = 'w';
    public const append = 'a';

    public function touch()
    {
        touch($this->getPath());
    }

    public function open(string $mode): Buffer
    {
        return new Buffer(fopen($this->getPath(), $mode));
    }

    public function append(string $data): bool
    {
        return file_put_contents($this->getPath(), $data, FILE_APPEND);
    }

    public function write(string $data): bool
    {
        return file_put_contents($this->getPath(), $data);
    }

    public function read(int $length = 0): string
    {
        if (0 == $length) {
            return file_get_contents($this->getPath());
        }

        return $this->open(self::readOnly)->read($length);
    }

    public function size(): int
    {
        return filesize($this->getPath());
    }

    public function move(File $dest): bool
    {
        if ($dest instanceof self) {
            return rename($this->getPath(), $dest->getPath());
        }
        if ($this->copyTo($dest)) {
            $this->delete();

            return true;
        }

        return false;
    }

    public function rename(string $newName): bool
    {
        if (rename($this->getPath(), $this->directory.'/'.$newName)) {
            $this->basename = $newName;

            return true;
        }

        return false;
    }

    public function delete()
    {
        unlink($this->getPath());
    }

    public function md5(): string
    {
        return md5_file($this->getPath());
    }

    public function sha1(): string
    {
        return sha1_file($this->getPath());
    }

    public function copyTo(File $dest): bool
    {
        if ($dest instanceof self) {
            return copy($this->getPath(), $dest->getPath());
        } else {
            return $dest->copyFrom($this);
        }
    }

    public function getDirectory(): Directory\Local
    {
        return new Directory\Local($this->directory);
    }

    public function exists(): bool
    {
        return is_file($this->getPath());
    }

    public function getRealPath(): string
    {
        return realpath($this->getPath());
    }

    public function isIn(Directory $parent): bool
    {
        if (!$this->exists() or !$parent->exists()) {
            return parent::isIn($parent);
        }
        if (!$parent instanceof Directory\Local) {
            return false;
        }
        $base = $parent->getRealPath().'/';

        return substr($this->getRealPath(), 0, strlen($base)) == $base;
    }

    public function getRelativePath(Directory $parent): string
    {
        if (!$this->exists() or !$parent->exists()) {
            return parent::getRelativePath($parent);
        }
        if (!$this->isIn($parent)) {
            throw new Exception("Currently cannot generate path for not nested nodes, parentPath: [{$parent->getPath()}] thisPath: [{$this->getPath()}]");
        }

        return substr($this->getRealPath(), strlen($parent->getRealPath()) + 1);
    }

    public function __serialize(): array
    {
        return [
            'directory' => $this->directory,
            'basename' => $this->basename,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->directory = $data['directory'] ?? null;
        $this->basename = $data['basename'] ?? null;
    }
}
