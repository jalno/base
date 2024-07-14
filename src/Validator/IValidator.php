<?php

namespace packages\base\Validator;

interface IValidator
{
    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array;

    /**
     * Validate data based on rule.
     *
     * @return mixed|null new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data);
}
