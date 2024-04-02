<?php

namespace packages\base\view;

use packages\base\Exception;

class Error extends Exception implements \JsonSerializable
{
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const FATAL = 'fatal';
    public const NOTICE = 'notice';

    public const NO_TRACE = 0;
    public const SHORT_TRACE = 1;
    public const FULL_TRACE = 2;

    protected $type = self::FATAL;
    protected $traceMode = self::FULL_TRACE;
    protected $code;
    protected $message;
    protected $data;
    protected $trace;

    public function __construct(string $code = null)
    {
        $this->code = $code;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setData($val, $key = null): void
    {
        if ($key) {
            $this->data[$key] = $val;
        } else {
            $this->data = $val;
        }
    }

    public function getData($key = null)
    {
        if ($key) {
            return isset($this->data[$key]) ? $this->data[$key] : null;
        } else {
            return $this->data;
        }
    }

    public function setType(string $type): void
    {
        if (!in_array($type, [self::SUCCESS, self::WARNING, self::FATAL, self::NOTICE])) {
            throw new Exception('type');
        }
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setTraceMode(int $traceMode): void
    {
        $this->traceMode = $traceMode;
    }

    public function getTraceMode(): int
    {
        return $this->traceMode;
    }

    public function __serialize(): array
    {
        return $this->jsonSerialize();
    }

    public function unserialize(array $data): void
    {
        $this->type = $data['type'];
        $this->traceMode = $data['traceMode'] ?? self::NO_TRACE;
        $this->code = $data['code'] ?? null;
        $this->message = $data['message'] ?? '';
        $this->data = $data['data'] ?? null;
        $this->file = $data['file'] ?? '';
        $this->line = $data['line'] ?? 0;
        $this->trace = $data['trace'] ?? '';
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
        ];
        if ($this->code) {
            $data['code'] = $this->code;
        }
        if ($this->message) {
            $data['message'] = $this->message;
        }
        if (null !== $this->data) {
            $data['data'] = $this->data;
        }
        if ($this->traceMode >= self::SHORT_TRACE) {
            $data['file'] = $this->file;
            $data['line'] = $this->line;
            $data['traceMode'] = $this->traceMode;
        }
        if (self::SHORT_TRACE == $this->traceMode) {
            $data['trace'] = $this->getTraceAsString();
        } elseif (self::FULL_TRACE == $this->traceMode) {
            $data['trace'] = $this->getTrace();
        }

        return $data;
    }
}
