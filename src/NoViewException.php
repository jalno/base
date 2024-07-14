<?php

namespace packages\base;

class NoViewException extends Exception
{
    /** @var string */
    private $viewName;

    public function __construct(string $viewName, string $message = '')
    {
        $this->viewName = $viewName;
        if (!$message) {
            $message = "Cannot find {$viewName} or any of its children";
        }

        return $viewName;
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }
}
