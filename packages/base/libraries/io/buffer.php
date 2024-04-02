<?php

namespace packages\base\IO;

class Buffer
{
    private $buffer;

    public function __construct($buffer)
    {
        if (!is_resource($buffer)) {
            throw new \InvalidArgumentException('Argument 1 passed to '.Buffer::class.'::__construct() must be of the type resource, '.gettype($buffer).' given');
        }
        $this->buffer = $buffer;
    }

    public function __destruct()
    {
        if ($this->buffer) {
            $this->close();
        }
    }

    public function close(): void
    {
        fclose($this->buffer);
        $this->buffer = null;
    }

    public function read(int $length): string
    {
        return fread($this->buffer, $length);
    }

    public function readLine(int $length = 0)
    {
        if (0 == $length) {
            return fgets($this->buffer);
        }

        return fgets($this->buffer, $length);
    }

    public function write(string $data): int
    {
        return fwrite($this->buffer, $data);
    }
}
