<?php

namespace packages\base\View\Events;

use packages\base\Event;
use packages\base\View;

trait ViewEvent
{
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }
}

