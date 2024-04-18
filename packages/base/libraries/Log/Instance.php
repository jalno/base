<?php

namespace packages\base\Log;

use packages\base\Json;
use packages\base\Log;

class Instance
{
    protected $level;
    protected $lastLevel;
    protected $lastMessage;
    protected $closed = false;
    protected $replyCharacter = '';
    protected $append = false;

    public function __construct($level)
    {
        Log::newChild();
        $this->setLevel($level);
    }

    public function __destruct()
    {
        $this->end();
    }

    public function end()
    {
        if (!$this->closed) {
            $this->closed = true;
            Log::dieChild();
        }
    }

    public function setLevel($level)
    {
        if (in_array($level, [
            Log::debug,
            Log::info,
            Log::warn,
            Log::error,
            Log::fatal,
            Log::off,
        ])) {
            $this->level = $level;
        }
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function debug()
    {
        return $this->log(Log::debug, func_get_args());
    }

    public function info()
    {
        return $this->log(Log::info, func_get_args());
    }

    public function warn()
    {
        return $this->log(Log::warn, func_get_args());
    }

    public function error()
    {
        return $this->log(Log::error, func_get_args());
    }

    public function fatal()
    {
        return $this->log(Log::fatal, func_get_args());
    }

    public function log($level, $data)
    {
        if ($data) {
            $check = $this->checkLevel($level);
            $this->lastLevel = $level;
            if ($check) {
                Log::write($level, $this->createMessage($data));
            }
            $this->append = false;
            $this->replyCharacter = '';
        }

        return $this;
    }

    public function append()
    {
        $this->replyCharacter = '';
        $this->append = true;

        return $this->log($this->lastLevel, func_get_args());
    }

    public function reply()
    {
        $this->replyCharacter = ': ';
        $this->append = true;

        return $this->log($this->lastLevel, func_get_args());
    }

    private function checkLevel($level)
    {
        return $this->level and $level >= $this->level;
    }

    private function createMessage($args)
    {
        $message = '';
        foreach ($args as $arg) {
            if ($message) {
                $message .= ' ';
            }
            $type = gettype($arg);
            if (in_array($type, ['array', 'object', 'boolean', 'NULL'])) {
                if ('object' == $type) {
                    $arg = (array) $arg;
                }
                $message .= json\encode($arg);
            } else {
                $message .= $arg;
            }
        }
        if ($this->append) {
            $message = $this->lastMessage.$this->replyCharacter.$message;
        }
        $this->lastMessage = $message;

        return $message;
    }
}
