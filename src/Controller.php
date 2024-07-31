<?php

namespace packages\base;

class Controller
{
    /** @var Response */
    protected $response;

    public function __construct()
    {
        $this->response = new Response(false);
    }

    /**
     * Run an input validator.
     *
     * @return array filtered data
     *
     * @throws InputValidationException
     */
    protected function checkinputs(array $rules)
    {
        $validator = new Validator($rules, array_replace_recursive(HTTP::$data, HTTP::$files));

        return $validator->validate();
    }

    protected function inputsvalue($fields)
    {
        $return = [];
        $formdata = HTTP::$data;
        foreach ($fields as $field => $options) {
            if (isset($formdata[$field])) {
                $return[$field] = $this->escapeFormData($formdata[$field]);
            } else {
                $return[$field] = '';
            }
        }

        return $return;
    }

    private function escapeFormData($data)
    {
        $return = [];
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($this->escapeFormData($val) as $key2 => $val2) {
                        $return[$key][$key2] = $val2;
                    }
                } else {
                    $return[$key] = $val;
                }
            }
        } else {
            $return = htmlspecialchars($data);
        }

        return $return;
    }

    public function response(Response $response)
    {
        $response->send();
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}
