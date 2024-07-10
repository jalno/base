<?php

namespace packages\base\IO\Drivers;

use packages\base\Exception;
use packages\base\IO\Drivers\FTP\AuthException;
use packages\base\IO\Drivers\FTP\CannectionException;
use packages\base\IO\Drivers\FTP\ChangeDirException;
use packages\base\IO\Drivers\FTP\NotReady;

class FTP
{
    public const BINARY = FTP_BINARY;
    public const ASCII = FTP_ASCII;
    private $defaultOptions = [
        'host' => '',
        'port' => 21,
        'username' => '',
        'password' => '',
        'passive' => true,
        'root' => '',
        'ssl' => false,
        'timeout' => 30,
    ];
    private $options;
    private $connection;
    private $ready = false;

    public function __construct($userOptions)
    {
        $this->options = array_replace($this->defaultOptions, $userOptions);
        if ($this->options['host'] and $this->options['port']) {
            if ($this->connect()) {
                if ($this->options['username'] and $this->options['password']) {
                    if ($this->login()) {
                        if ($this->options['root']) {
                            if (!$this->chdir($this->options['root'])) {
                                throw new ChangeDirException($this->options['root']);
                            }
                        }
                        $this->ready = true;
                    }
                }
            }
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    private function connect()
    {
        $function = $this->options['ssl'] ? 'ftp_ssl_connect' : 'ftp_connect';
        if ($this->connection = $function($this->options['host'], $this->options['port'], $this->options['timeout'])) {
            return true;
        } else {
            throw new CannectionException();
        }
    }

    private function login()
    {
        if (ftp_login($this->connection, $this->options['username'], $this->options['password'])) {
            if ($this->options['passive']) {
                ftp_pasv($this->connection, true);
            }

            return true;
        } else {
            throw new AuthException();
        }
    }

    public function close()
    {
        if ($this->connection) {
            ftp_close($this->connection);
            $this->connection = null;
        }
    }

    public function chdir(string $dir)
    {
        if ($this->ready) {
            return @ftp_chdir($this->connection, $dir);
        } else {
            throw new NotReady();
        }
    }

    public function cdup()
    {
        if (!$this->ready) {
            throw new NotReady();
        }

        return @ftp_cdup($this->connection);
    }

    public function pwd()
    {
        if (!$this->ready) {
            throw new NotReady();
        }

        return @ftp_pwd($this->connection);
    }

    public function put($local, $remote, $mode = self::BINARY, $startpos = 0)
    {
        if ($this->ready) {
            return ftp_put($this->connection, $remote, $local, $mode, $startpos);
        } else {
            throw new NotReady();
        }
    }

    public function get($remote, $local, $mode = self::BINARY, $startpos = 0)
    {
        if ($this->ready) {
            return ftp_get($this->connection, $local, $remote, $mode, $startpos);
        } else {
            throw new NotReady();
        }
    }

    public function rename($oldname, $newname): bool
    {
        if ($this->ready) {
            return ftp_rename($this->connection, $oldname, $newname);
        } else {
            throw new NotReady();
        }
    }

    public function delete($path): bool
    {
        if (!$this->ready) {
            throw new NotReady();
        }

        return ftp_delete($this->connection, $path);
    }

    public function rmdir(string $dir): bool
    {
        return @ftp_rmdir($this->connection, $dir);
    }

    public function is_ready()
    {
        return $this->ready;
    }

    public function is_dir(string $filename): bool
    {
        $pwd = $this->pwd();
        if (!$this->chdir($filename)) {
            return false;
        }

        return $this->chdir($pwd);
    }

    public function size($path): int
    {
        if (!$this->ready) {
            throw new NotReady();
        }

        return ftp_size($this->connection, $path);
    }

    public function is_file(string $filename): bool
    {
        return -1 != $this->size($filename);
    }

    public function listOfFiles(string $dir, bool $dirs = true, bool $subdirs = false): array
    {
        $items = [];
        $files = $this->nlist($dir);
        foreach ($files as $file) {
            if ($this->is_dir($file)) {
                if ($dirs) {
                    $items[] = $file;
                }
                if ($subdirs) {
                    $items = array_merge($items, $this->listOfFiles($file, $dirs, $subdirs));
                }
            } else {
                $items[] = $file;
            }
        }

        return $items;
    }

    public function nlist(string $dir): array
    {
        if (!$this->ready) {
            throw new NotReady();
        }
        $list = @ftp_nlist($this->connection, $dir);
        if (!is_array($list)) {
            $list = [];
        }

        return $list;
    }

    public function rawList(string $dir): array
    {
        if (!$this->ready) {
            throw new NotReady();
        }
        $list = @ftp_rawlist($this->connection, $dir);
        if (!is_array($list)) {
            $list = [];
        }

        return $list;
    }

    public function list(string $dir): array
    {
        $raw = $this->rawList($dir);
        $list = [];
        foreach ($raw as $line) {
            if (!preg_match("/^([\-dbclps])([\-rwxst]{9})\\s+(\\d+)\\s+([\\w-]+)\\s+([\\w-]+)\\s+(\\d+)\\s+(\\w{3}\\s+\\d{1,2}\\s+(?:\\d{1,2}:\\d{1,2}|\\d{4}))\\s+(.+)$/", $line, $matches)) {
                throw new Exception("invalid line: {$line}");
            }
            $list[] = [
                'type' => '-' != $matches[1] ? $matches[1] : 'f',
                'permissions' => $matches[2],
                'items' => intval($matches[3]),
                'owner' => $matches[4],
                'group' => $matches[5],
                'size' => intval($matches[6]),
                'date' => $matches[7],
                'name' => $matches[8],
            ];
        }

        return $list;
    }

    public function chmod(string $dir, int $mode): bool
    {
        return @ftp_chmod($this->connection, $mode, $dir);
    }

    public function mkdir(string $pathname, int $mode = 755): bool
    {
        $pwd = $this->pwd();
        $parts = explode('/', $pathname);
        foreach ($parts as $part) {
            if ($part and !$this->chdir($part)) {
                if (!ftp_mkdir($this->connection, $part)) {
                    return false;
                }
                $this->chdir($part);
                if (755 != $mode) {
                    $this->chmod($part, $mode);
                }
            }
        }
        $this->chdir($pwd);

        return true;
    }

    public function getHostname(): string
    {
        return $this->options['host'];
    }

    public function getPort(): int
    {
        return $this->options['port'];
    }

    public function getUsername(): string
    {
        return $this->options['username'];
    }

    public function getPassword(): string
    {
        return $this->options['password'];
    }
}
