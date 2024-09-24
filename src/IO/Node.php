<?php

namespace packages\base\IO;

use packages\base\Exception;

abstract class Node
{
    public string $directory;
    public string $basename;

    public function __construct(string $path = '')
    {
        $this->basename = basename($path);
        $this->directory = dirname($path);
        if (DIRECTORY_SEPARATOR === $this->directory) {
            $this->directory = '';
        }
    }

    public function getPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.$this->basename;
    }

    abstract public function rename(string $newName): bool;

    abstract public function delete();

    abstract public function getDirectory();

    public function isIn(Directory $parent): bool
    {
        if ($parent === $this) {
            return true;
        }
        if (!is_a($this->getDirectory(), get_class($parent), false)) {
            return false;
        }
        if ($this->getPath() === $parent->getPath()) {
            return false;
        }
        return str_starts_with($this->getPath(), $parent->getPath().DIRECTORY_SEPARATOR);
    }

    public function getRelativePath(Directory $parent): string
    {
        if (!$this->isIn($parent)) {
            throw new Exception("Currently cannot generate path for not nested nodes, parentPath: {$parent->getPath()} , thisPath: {$this->getPath()}");
        }

        return substr($this->getPath(), strlen($parent->getPath()) + 1);
    }

    abstract public function exists(): bool;
}
