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
class BeforeLoad extends Event
{
    use ViewEvent;
}
class AfterLoad extends Event
{
    use ViewEvent;
}
class AfterOutput extends Event
{
    use ViewEvent;
}
