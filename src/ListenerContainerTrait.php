<?php

namespace packages\base;

use Illuminate\Support\Facades\Event;

trait ListenerContainerTrait
{

    /**
     * register a listener for a event.
     *
     * @param string $event    full event name
     * @param string $listener must be in format: {summerized listener class}@{method name}
     */
    public function addEvent(string $event, string $listener): void
    {
        $event = str_replace("/", "\\", $event);
        $listener = str_replace("/", "\\", $listener);
        Event::listen($event, $listener);
    }
}
