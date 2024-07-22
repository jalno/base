<?php

namespace packages\base\Validator;

use packages\base\Exception;
use packages\base\Http;
use packages\base\InputValidationException;
use packages\base\IO\Directory;
use packages\base\IO\File;

class FileValidator implements IValidator
{
    public static $tmpDirectories = [];

    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return ['file'];
    }

    /**
     * Validate data to be a email.
     *
     * @return packages\base\IO\file\local|null new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
        // To prevent user send a $_FILE-like field using post or get data, we will check http::$files directly.
        if (!is_array($data) or ((!isset($rule['prevent-reality-check']) or !$rule['prevent-reality-check']) and !isset(HTTP::$files[$input]))) {
            throw new InputValidationException($input);
        }
        if (!isset($data['error'])) {
            if (!isset($rule['multiple']) or !$rule['multiple']) {
                throw new InputValidationException($input);
            }
            $files = null;
            $x = 0;
            foreach ($data as $file) {
                $result = $this->validateSingleFile($input."[{$x}]", $rule, $file);
                if ($result) {
                    if (is_object($result) and $result instanceof NullValue) {
                        return $result;
                    }
                    if (null === $files) {
                        $files = [];
                    }
                    $files[] = $result;
                } else {
                    $files[] = $file;
                }
                ++$x;
            }

            return $files;
        }

        return $this->validateSingleFile($input, $rule, $data);
    }

    protected function validateSingleFile(string $input, array $rule, $data)
    {
        if (UPLOAD_ERR_NO_FILE == $data['error']) {
            if (!isset($rule['optional']) or !$rule['optional']) {
                throw new InputValidationException($input);
            }
            if (isset($rule['default'])) {
                return $rule['default'];
            }

            return new NullValue();
        }
        if (UPLOAD_ERR_OK != $data['error']) {
            throw new InputValidationException($input, "file error: {$data['error']}");
        }
        if (isset($rule['extension']) and $rule['extension']) {
            if (!is_array($rule['extension'])) {
                $rule['extension'] = [$rule['extension']];
            }
            $extension = strtolower(substr($data['name'], strrpos($data['name'], '.') + 1));
            if (!in_array($extension, $rule['extension'])) {
                throw new InputValidationException($input, 'extension');
            }
        }
        if (false !== strpos($data['name'], '..') or false !== strpos($data['name'], '/') or false !== strpos($data['name'], '\\')) {
            throw new InputValidationException($input, 'bad-name');
        }
        if (isset($rule['min-size']) and $rule['min-size'] > 0 and $data['size'] < $rule['min-size']) {
            throw new InputValidationException($input, 'min-size');
        }
        if (isset($rule['max-size']) and $rule['max-size'] > 0 and $data['size'] > $rule['max-size']) {
            throw new InputValidationException($input, 'max-size');
        }
        if (isset($rule['obj']) and $rule['obj']) {
            return $this->renameToOriginal($data);
        }
    }

    private function diverseArray(array $vector): array
    {
        $result = [];
        foreach ($vector as $key1 => $value1) {
            foreach ($value1 as $key2 => $value2) {
                $result[$key2][$key1] = $value2;
            }
        }

        return $result;
    }

    private function renameToOriginal(array $file): File
    {
        $dir = new Directory\TMP();
        self::$tmpDirectories[] = $dir;
        $obj = $dir->file($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $obj->getPath())) {
            throw new Exception('cannot move uploaded file');
        }

        return $obj;
    }
}
