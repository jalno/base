<?php

namespace packages\base\IO\directory;

use packages\base\IO\directory;
use packages\base\IO\drivers\ftp as driver;
use packages\base\IO\file;

class ftp extends Directory
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
     * Calcute sum of all files (including files in subdirectories).
     */
    public function size(): int
    {
        $size = 0;
        foreach ($this->files(true) as $file) {
            $size += $file->size();
        }

        return $size;
    }

    /**
     * Move a file to anthor directory.
     *
     * @param \packages\base\IO\directory $dest destination path
     */
    public function move(directory $dest): bool
    {
        if (!$dest->exists()) {
            $dest->make(true);
        }
        if ($this->getDriver()->rename($this->getPath(), $dest->getPath().'/'.$this->basename)) {
            $this->directory = $dest->getPath();

            return true;
        }

        return false;
    }

    /**
     * Set new name for directory.
     */
    public function rename(string $newName): bool
    {
        if ($this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName)) {
            $this->basename = $newName;

            return true;
        }

        return false;
    }

    /**
     * Delete the directory and all of Its files from ftp server.
     */
    public function delete(): void
    {
        foreach ($this->items(false) as $item) {
            $item->delete();
        }
        $this->getDriver()->rmdir($this->getPath());
    }

    /**
     * Make the directory on FTP server.
     *
     * @param bool $recursive default: false
     */
    public function make(bool $recursive = false): bool
    {
        $driver = $this->getDriver();

        return $driver->mkdir($this->getPath());
    }

    /**
     * Return files in this directory.
     *
     * @param bool $recursively search subdirectories or not. default: false
     *
     * @return \packages\base\IO\file\ftp[]
     */
    public function files(bool $recursively = false): array
    {
        $driver = $this->getDriver();
        $scanner = function ($dir) use ($recursively, $driver, &$scanner) {
            $items = [];
            foreach ($driver->list($dir) as $item) {
                if ('.' == $item['name'] or '..' == $item['name']) {
                    continue;
                }
                if ('f' == $item['type']) {
                    $file = new file\ftp($dir.'/'.$item['name']);
                    $file->setDriver($driver);
                    $items[] = $file;
                } elseif ('d' == $item['type'] and $recursively) {
                    $items = array_merge($items, $scanner($dir.'/'.$item['name']));
                }
            }

            return $items;
        };

        return $scanner($this->getPath());
    }

    /**
     * Return subdirectories in this directory.
     *
     * @param bool $recursively search subdirectories or not. default: false
     *
     * @return \packages\base\IO\directory\ftp[]
     */
    public function directories(bool $recursively = true): array
    {
        $driver = $this->getDriver();
        $scanner = function ($dir) use ($recursively, $driver, &$scanner) {
            $items = [];
            foreach ($driver->list($dir) as $item) {
                if ('.' == $item['name'] or '..' == $item['name']) {
                    continue;
                }
                if ('d' == $item['type']) {
                    $directory = new directory\ftp($dir.'/'.$item['name']);
                    $directory->setDriver($driver);
                    $items[] = $directory;
                    if ($recursively) {
                        $items = array_merge($items, $scanner($dir.'/'.$item['name']));
                    }
                }
            }

            return $items;
        };

        return $scanner($this->getPath());
    }

    /**
     * Return subdirectories and files in this directory.
     *
     * @param bool $recursively search subdirectories or not. default: false
     *
     * @return array<\packages\base\IO\file\ftp|\packages\base\IO\directory\ftp>
     */
    public function items(bool $recursively = true): array
    {
        $driver = $this->getDriver();
        $scanner = function ($dir) use ($recursively, $driver, &$scanner) {
            $items = [];
            foreach ($driver->list($dir) as $item) {
                if ('.' == $item['name'] or '..' == $item['name']) {
                    continue;
                }
                if ('d' == $item['type']) {
                    $directory = new directory\ftp($dir.'/'.$item['name']);
                    $directory->setDriver($driver);
                    $items[] = $directory;
                    if ($recursively) {
                        $items = array_merge($items, $scanner($dir.'/'.$item['name']));
                    }
                } elseif ('f' == $item['type']) {
                    $file = new file\ftp($dir.'/'.$item['name']);
                    $file->setDriver($driver);
                    $items[] = $file;
                }
            }

            return $items;
        };

        return $scanner($this->getPath());
    }

    /**
     * Check existance of the directory.
     */
    public function exists(): bool
    {
        return $this->getDriver()->is_dir($this->getPath());
    }

    /**
     * Retrun file object.
     *
     * @return \packages\base\IO\file\ftp
     */
    public function file(string $name): file\ftp
    {
        $file = new file\ftp($this->getPath().'/'.$name);
        $file->setDriver($this->getDriver());

        return $file;
    }

    /**
     * Retrun directory object.
     *
     * @return \packages\base\IO\directory\ftp
     */
    public function directory(string $name): directory\ftp
    {
        $directory = new directory\ftp($this->getPath().'/'.$name);
        $directory->setDriver($this->getDriver());

        return $directory;
    }

    /**
     * Return parent directory.
     *
     * @return \packages\base\IO\directory\ftp
     */
    public function getDirectory(): directory\ftp
    {
        $directory = new directory\ftp($this->basename);
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
