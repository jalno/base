<?php

namespace packages\base\Validator;

use packages\base\InputValidationException;
use packages\base\Validator;

class ArrayValidator implements IValidator
{
    private const MODE_ASSOC = 1;
    private const MODE_NUMERIC = 2;

    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['array'];
    }

    /**
     * Validate data to be a boolean value.
     *
     * @return mixed|null new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
        if (!is_array($data)) {
            if (!$data) {
                if (!isset($rule['empty']) or !$rule['empty']) {
                    throw new InputValidationException($input, 'empty');
                }

                return [];
            }
            if (isset($rule['convert-to-array']) and !$rule['convert-to-array']) {
                throw new InputValidationException($input, 'non-array');
            }
            if (isset($rule['explode'])) {
                if (!is_string($data)) {
                    throw new InputValidationException($input, 'non-string');
                }
                $data = explode($rule['explode'], $data);
            } else {
                $data = [$data];
            }
        }
        $mode = null;
        if (isset($rule['assoc']) and $rule['assoc']) {
            $mode = self::MODE_ASSOC;
        }
        if (isset($rule['numeric'])) {
            if ($rule['numeric']) {
                if ($mode) {
                    throw new \InvalidArgumentException('only one of assoc or numeric mode can be true');
                }
                $mode = self::MODE_NUMERIC;
            }
        } elseif (null === $mode) {
            $mode = self::MODE_NUMERIC;
        }

        if (self::MODE_NUMERIC == $mode) {
            if (array_keys($data) !== range(0, count($data) - 1)) {
                throw new InputValidationException($input, 'non-numeric-keys');
            }
        }

        if (isset($rule['filter'])) {
            $data = array_filter($data, $rule['filter']);
        }

        if (!isset($rule['duplicate'])) {
            $rule['duplicate'] = false;
        }
        if (false === $rule['duplicate']) {
            $processed = [];
            foreach ($data as $x => $value) {
                if (array_search($value, $processed)) {
                    throw new InputValidationException($input."[{$x}]", 'duplicate');
                }
                $processed[] = $value;
            }
            unset($processed);
        } elseif ('remove' === $rule['duplicate']) {
            $data = array_unique($data);
        }

        if (isset($rule['min']) and count($data) < $rule['min']) {
            throw new InputValidationException($input, 'min');
        }
        if (isset($rule['max']) and count($data) > $rule['max']) {
            throw new InputValidationException($input, 'max');
        }
        if (isset($rule['count']) and count($data) != $rule['count']) {
            throw new InputValidationException($input, 'count');
        }

        if (isset($rule['rules'])) {
            $data = (new Validator($rule['rules'], $data, $input))->validate();
        } elseif (isset($rule['each'])) {
            $data = $this->processEach($input, $rule, $data);
        }

        $this->processSort($rule, $data);

        return $data;
    }

    protected function processEach(string $input, array $rule, $data)
    {
        if (is_string($rule['each'])) {
            $rule['each'] = [
                'type' => $rule['each'],
            ];
        } elseif (!is_array($rule['each'])) {
            throw new \InvalidArgumentException('each must be string or array');
        }
        if (!isset($rule['each']['type'])) {
            throw new \InvalidArgumentException('each rule must have type');
        }
        $validator = Validator::resolve($rule['each']['type']);
        foreach ($data as $key => $value) {
            if (is_callable($validator)) {
                $newData = call_user_func($validator, $value, $rule['each'], $input."[{$key}]");
            } else {
                $newData = $validator->validate($input."[{$key}]", $rule['each'], $value);
            }
            if ($newData instanceof NullValue) {
                $data[$key] = null;
            } elseif (null !== $newData) {
                $data[$key] = $newData;
            }
        }

        return $data;
    }

    protected function processSort(array $rule, array &$data): void
    {
        if (isset($rule['sort'])) {
            if (is_int($rule['sort'])) {
                switch ($rule['sort']) {
                    case 1:
                        $rule['sort'] = 'sort';
                        break;
                    case -1:
                        $rule['sort'] = 'rsort';
                        break;
                }
            }
            call_user_func_array($rule['sort'], [&$data]);
        }
    }
}
