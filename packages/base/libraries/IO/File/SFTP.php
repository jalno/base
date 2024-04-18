<?php

namespace packages\base\IO\File;

use packages\base\Exception;
use packages\base\IO\Buffer;
use packages\base\IO\Directory;
use packages\base\IO\Drivers\SFTP as Driver;
use packages\base\IO\File;
use packages\base\IO\IStreamableFile;
use packages\base\SSH;

class SFTP extends File implements IStreamableFile
{
    public $hostname;
    public $port;
    public $username;
    public $password;
    private $driver;

    public function setDriver(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function getDriver(): Driver
    {
        if ($this->driver) {
            return $this->driver;
        }
        $ssh = new SSH($this->hostname, $this->port);
        if (!$ssh->AuthByPassword($this->username, $this->password)) {
            throw new Exception();
        }
        $this->driver = new Driver($ssh);

        return $this->driver;
    }

    public function open(string $mode): Buffer
    {
        return $this->getDriver()->open($this->getPath(), $mode);
    }

    public function write(string $data): bool
    {
        return $this->getDriver()->put_contents($this->getPath(), $data);
    }

    public function read(int $length = 0): string
    {
        if (0 == $length) {
            return $this->getDriver()->get_contents($this->getPath());
        }
        $buffer = $this->open('r');

        return $buffer->read($length);
    }

    public function size(): int
    {
        return $this->getDriver()->size($this->getPath());
    }

    public function move(File $dest): bool
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

    public function rename(string $newName): bool
    {
        return $this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName);
    }

    public function delete()
    {
        $this->getDriver()->unlink($this->getPath());
    }

    public function chmod(int $mode): bool
    {
        return $this->getDriver()->chmod($this->getPath(), $mode);
    }

    public function exists(): bool
    {
        return false != $this->getStat();
    }

    public function copyTo(File $dest): bool
    {
        $driver = $this->getDriver();
        if ($dest instanceof Local) {
            return $driver->download($this->getPath(), $dest->getPath());
        } else {
            $tmp = new TMP();
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
            return $this->getDriver()->upload($source->getPath(), $this->getPath());
        } else {
            $tmp = new TMP();
            if ($source->copyTo($tmp)) {
                return $this->copyFrom($tmp);
            }
        }

        return false;
    }

    public function getDirectory(): Directory\SFTP
    {
        $directory = new Directory\SFTP($this->directory);
        $directory->setDriver($this->getDriver());

        return $directory;
    }

    /**
     * @return array or false if faild to find file
     */
    public function getStat()
    {
        return $this->getDriver()->stat($this->getPath());
    }

    public function __serialize(): array
    {
        $driver = $this->getDriver();
        $data = [
            'directory' => $this->directory,
            'basename' => $this->basename,
        ];
        if ($this->hostname) {
            $data['hostname'] = $this->hostname;
        } elseif ($driver) {
            $data['hostname'] = $driver->getSSH()->getHost();
        }

        if ($this->port) {
            $data['port'] = $this->port;
        } elseif ($driver) {
            $data['port'] = $driver->getSSH()->getPort();
        }

        if ($this->username) {
            $data['username'] = $this->username;
        } elseif ($driver) {
            $data['username'] = $driver->getSSH()->getUsername();
        }

        if ($this->password) {
            $data['password'] = $this->password;
        } elseif ($driver) {
            $data['password'] = $driver->getSSH()->getPassword();
        }

        return $data;
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
