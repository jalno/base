<?php

namespace packages\base\views;

use packages\base\db;
use packages\base\InputValidation;
use packages\base\View\Error;

class FormError extends Error
{
    public const INFO = 'notice';
    public const DATA_VALIDATION = 'data_validation';
    public const DATA_DUPLICATE = 'data_duplicate';

    public static function fromException(\Exception $exception): FormError
    {
        $error = new static();
        $error->setType(self::FATAL);
        if (method_exists($exception, 'getInput')) {
            $error->input = $exception->getInput();
        }
        if ($exception instanceof db\InputDataType or $exception instanceof InputValidation) {
            $error->setCode(self::DATA_VALIDATION);
        } elseif ($exception instanceof db\DuplicateRecord) {
            $error->setCode(self::DATA_DUPLICATE);
        }

        return $error;
    }

    /**
     * @var string|null
     */
    public $input;

    public function __construct(string $code = null, string $input = null)
    {
        $this->code = $code;
        $this->input = $input;
    }

    /**
     * Setter for input property.
     *
     * @param string|null $input input name
     */
    public function setInput(string $input): void
    {
        $this->input = $input;
    }

    /**
     * Getter for input property.
     */
    public function getInput(): ?string
    {
        return $this->input;
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        if ($this->input) {
            $data['input'] = $this->input;
        }
        if ($this->code) {
            $data['error'] = $this->code;
        }

        return $data;
    }
}
