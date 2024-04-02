<?php

namespace packages\base\json;

use packages\base\Exception;

class JsonException extends Exception
{
    /** @var int|null */
    private $error;

    /**
     * @param string|null $message if was null it filled by json_last_error_msg()
     * @param int|null    $error   if was null it filled by json_last_error()
     */
    public function __construct(string $message = null, int $error = null)
    {
        if (null === $message) {
            $message = json_last_error_msg();
        }
        if (null === $error) {
            $error = json_last_error();
        }
        parent::__construct($message, $error);
    }

    /**
     * Getter for error code.
     */
    public function getError(): ?int
    {
        return $this->error;
    }
}
