<?php

namespace packages\base;

class InputValidation extends Exception
{
    /** @var string input name */
    protected $input;

    /**
     * @var string
     * @var string
     */
    public function __construct(string $input, string $message = '')
    {
        $this->input = $input;
        parent::__construct($message);
    }

    /**
     * Getter for input value.
     */
    public function getInput(): string
    {
        return $this->input;
    }

    /**
     * Setter for input value.
     *
     * @return string
     */
    public function setInput(string $input): void
    {
        $this->input = $input;
    }
}
