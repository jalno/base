<?php

namespace packages\base\IO\file;

use packages\base\IO\directory;
use packages\base\IO\drivers\ftp as driver;
use packages\base\IO\file;
use packages\base\IO\ReadException;

class ftp extends file
{
    /** @var string|null */
    public $hostname;

    /** @var int|null */
    public $port = 21;

    /** @var string|null */
    public $username;

    /** @var string|null */
    public $password;

    /** @var \packages\base\IO\drivers\ftp|null */
    private $driver;

    /**
     * Setter for FTP driver.
     */
    public function setDriver(driver $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Getter for FTP driver.
     */
    public function getDriver(): driver
    {
        if ($this->driver) {
            return $this->driver;
        }
        $this->driver = new driver([
            'host' => $this->hostname,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
        ]);

        return $this->driver;
    }

    /**
     * Write content to file.
     */
    public function write(string $data): bool
    {
        $tmp = new tmp();
        $tmp->write($data);

        return $this->copyFrom($tmp);
    }

    /**
     * Read content.
     */
    public function read(int $length = 0): string
    {
        $tmp = new tmp();
        if (!$this->copyTo($tmp)) {
            throw new ReadException($this);
        }

        return $tmp->read($length);
    }

    /**
     * get size of file.
     */
    public function size(): int
    {
        return $this->getDriver()->size($this->getPath());
    }

    /**
     * move file to anthor destination.
     */
    public function move(file $dest): bool
    {
        if ($dest instanceof self) {
            return $this->getDriver()->rename($this->getPath(), $dest->getPath());
        }
        if ($this->copyTo($dest)) {
            $this->delete();

            return true;
        }

        return false;
    }

    /**
     * Rename the file.
     */
    public function rename(string $newName): bool
    {
        return $this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName);
    }

    /**
     * Delete the file.
     */
    public function delete(): bool
    {
        return $this->getDriver()->delete($this->getPath());
    }

    /**
     * Copy content of the file to anthor.
     */
    public function copyTo(file $dest): bool
    {
        if ($dest instanceof local) {
            return $this->getDriver()->get($this->getPath(), $dest->getPath());
        } else {
            $tmp = new tmp();
            if ($this->copyTo($tmp)) {
                return $tmp->copyTo($dest);
            }
        }

        return false;
    }

    /**
     * Copy content of a another file to current file.
     */
    public function copyFrom(File $source): bool
    {
        if ($source instanceof Local) {
            return $this->getDriver()->put($source->getPath(), $this->getPath());
        } else {
            $tmp = new TMP();
            if ($source->copyTo($tmp)) {
                return $this->copyFrom($tmp);
            }
        }

        return false;
    }

    /**
     * check existance of the file.
     */
    public function exists(): bool
    {
        return $this->getDriver()->is_file($this->getPath());
    }

    /**
     * Return parent directory.
     *
     * @return \packages\base\IO\directory\ftp
     */
    public function getDirectory(): directory\ftp
    {
        $directory = new directory\ftp($this->directory);
        $directory->setDriver($this->getDriver());

        return $directory;
    }

    public function __serialize(): array
    {
        if (!$this->hostname) {
            $this->hostname = $this->getDriver()->getHostname();
        }
        if (!$this->port) {
            $this->port = $this->getDriver()->getPort();
        }
        if (!$this->username) {
            $this->username = $this->getDriver()->getUsername();
        }
        if (!$this->password) {
            $this->password = $this->getDriver()->getPassword();
        }

        return [
            'directory' => $this->directory,
            'basename' => $this->basename,
            'hostname' => $this->hostname,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->directory = $data['directory'] ?? null;
        $this->basename = $data['basename'] ?? null;
        $this->hostname = $data['hostname'] ?? null;
        $this->port = $data['port'] ?? 21;
        $this->username = $data['username'] ?? null;
        $this->password = $data['password'] ?? null;
    }
}
