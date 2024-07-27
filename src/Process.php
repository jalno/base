<?php

namespace packages\base;

use packages\base\DB\DBObject;
use packages\base\Process\Exceptions\CannotStartProcessException;
use packages\base\Process\Exceptions\InterruptedException;
use packages\base\Process\Exceptions\NotSavedProcessException;
use packages\base\Process\Exceptions\NotShellAccessException;
use packages\base\Process\Exceptions\NotStartedProcessException;
use packages\base\View\Error;

class Process extends DBObject
{
    public const SIGHUP = 1;
    public const SIGINT = 2;
    public const SIGQUIT = 3;
    public const SIGILL = 4;
    public const SIGTRAP = 5;
    public const SIGABRT = 6;
    public const SIGBUS = 7;
    public const SIGFPE = 8;
    public const SIGKILL = 9;
    public const SIGUSR1 = 10;
    public const SIGSEGV = 11;
    public const SIGUSR2 = 12;
    public const SIGPIPE = 13;
    public const SIGALRM = 14;
    public const SIGTERM = 15;
    public const SIGSTKFLT = 16;
    public const SIGCHLD = 17;
    public const SIGCONT = 18;
    public const SIGSTOP = 19;
    public const SIGTSTP = 20;
    public const SIGTTIN = 21;
    public const SIGTTOU = 22;
    public const SIGURG = 23;
    public const SIGXCPU = 24;
    public const SIGXFSZ = 25;
    public const SIGVTALRM = 26;
    public const SIGPROF = 27;
    public const SIGWINCH = 28;
    public const SIGIO = 29;
    public const SIGPWR = 30;
    public const SIGSYS = 31;

    public const stopped = 0;
    public const running = 1;
    public const error = 2;
    public const OS_WINDOWS = 1;
    public const OS_NIX = 2;
    public const OS_OTHER = 3;

    protected $dbTable = 'base_processes';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'name' => ['type' => 'text', 'required' => true],
        'pid' => ['type' => 'int'],
        'start' => ['type' => 'int'],
        'end' => ['type' => 'int'],
        'parameters' => ['type' => 'text'],
        'response' => ['type' => 'text'],
        'progress' => ['type' => 'int'],
        'status' => ['type' => 'int'],
    ];
    protected $serializeFields = ['response', 'parameters'];

    protected function preLoad($data)
    {
        if ($this->isNew) {
            if (!isset($data['status'])) {
                $data['status'] = self::stopped;
            }
        }

        return $data;
    }

    /**
     * Runs the command in a background process.
     */
    public function background_run(): void
    {
        $this->checkOS();

        if (!function_exists('shell_exec')) {
            throw new NotShellAccessException();
        }
    
        if (!$this->id) {
            throw new NotSavedProcessException();
        }
        $artsian = $this->getArtsianPath();

        $php = Options::get('packages.base.process.php-bin') ?: "php";
        $command = $php.' '. $artsian . ' --process='.$this->id;
        $pid = (int) shell_exec("$command > /dev/null  2>&1 & echo $!");
        if ($pid <= 0) {
            throw new CannotStartProcessException($this);
        }
        $this->pid = $pid;
        $this->save();
    }

    public function setPID(): void
    {
        $this->pid = getmypid();
        $this->save();
    }

    public function runAndWaitFor(int $seconds = 0, bool $throwable = true)
    {
        $this->background_run();
        if ($this->waitFor($seconds, $throwable)) {
            $this->byId($this->id);

            return $this->response;
        } else {
            return self::running;
        }
    }

    /**
     * Call a process method.
     * All runtime throws of process will caught and save in process response.
     *
     * @return Response|null if process successfully return a Response object, it will return. In case of exception null will return.
     *
     * @throws Exception if Could not find process
     * @throws Exception if the process is alread running
     */
    public function run(): ?Response
    {
        if (self::running == $this->status) {
            throw new Exception("Process #{$this->id} already running");
        }
        $this->status = self::running;
        $this->start = Date::time();
        $this->end = null;
        $this->setPID();
        $this->save();

        $return = null;
        try {
            $return = app()->call($this->name, ['data' => $this->parameters]);
            if ($return instanceof Response) {
                $this->status = $return->getStatus() ? self::stopped : self::error;
                $this->response = $return;
                if ($return->getStatus()) {
                    $this->progress = 100;
                }
            } else {
                $this->status = self::stopped;
            }
        } catch (\Throwable $e) {
            fwrite(STDERR, (string) $e);
            $this->status = Process::error;
            if ($e instanceof Error) {
                $e->setTraceMode(Error::SHORT_TRACE);
            }
            $this->response = $e;
        }
        $this->end = Date::time();
        $this->save();

        return $return;
    }

    /**
     * Returns if the process is currently running.
     *
     * @return bool TRUE if the process is running, FALSE if not
     */
    public function isRunning(): bool
    {
        $this->checkOS();

        if (!$this->pid) {
            throw new NotStartedProcessException($this);
        }

        return file_exists('/proc/'.$this->pid);
    }

    /**
     * Returns if the process interrupted by anthor process.
     */
    protected function isInterrupted(): bool
    {
        if (!$this->pid) {
            throw new NotStartedProcessException($this);
        }

        return 1 == Cache::get('packages.base.process.'.$this->pid.'.interrupt');
    }

    /**
     * throws exceptions if the process interrupted by anthor process.
     *
     * @return void
     *
     * @throws InterruptedException
     */
    protected function checkInterruption(): void
    {
        if ($this->isInterrupted()) {
            throw new InterruptedException();
        }
    }

    /**
     * Interrupt anthor process to stop or pause it.
     *
     * @return void
     */
    public function interrupt(): void
    {
        if (!$this->pid) {
            throw new NotStartedProcessException($this);
        }
        Cache::set('packages.base.process.'.$this->pid.'.interrupt', 1);
    }

    /**
     * Stops the process.
     *
     * @return bool `true` if the processes was stopped, `false` otherwise
     */
    public function stop(int $signal = self::SIGTERM, int $timeout = 10): bool
    {
        if (isset($this->pid)) {
            if ($this->isRunning()) {
                shell_exec('kill -'.$signal.' '.$this->pid.' 2>&1');
                $start = time();
                while ((time() - $start < $timeout) and $this->isRunning()) {
                }

                return !$this->isRunning();
            }
        } else {
            throw new Process\Exceptions\NotStartedProcessException($this);
        }

        return false;
    }

    protected function checkOS(): void
    {
        if (self::OS_NIX != self::getOS()) {
            throw new NotShellAccessException();
        }

    }

    protected function progress(int|float $progress): void
    {
        $this->progress += $progress;
        $this->save();
    }

    public static function getOS(): int
    {
        $os = strtoupper(PHP_OS);
        if ('WIN' === substr($os, 0, 3)) {
            return self::OS_WINDOWS;
        } elseif ('LINUX' === $os || 'FREEBSD' === $os || 'DARWIN' === $os) {
            return self::OS_NIX;
        }

        return self::OS_OTHER;
    }

    public function waitFor(int $timeout = 0, bool $throwable = true): bool
    {
        $ftime = time();
        while ($this->isRunning() and (0 == $timeout or time() - $ftime < $timeout)) {
            usleep(250000);
        }
        $this->where('id', $this->id);
        $this->getOne();
        if ($throwable and $this->response instanceof \Exception) {
            throw $this->response;
        }

        return !$this->isRunning();
    }

    public static function getArtsianPath(): string
    {
        $path = Options::get("packages.base.process.artisan", app()->basePath("artisan"));
        if (!is_file($path)) {
            throw new Exception("Cannot find artsian file in '{$path}'");
        }
        return $path;
    }
}
