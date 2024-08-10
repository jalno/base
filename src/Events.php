<?php

namespace packages\base;

use Illuminate\Support\Facades\Event;

class Events
{
    public static function trigger(EventInterface $event)
    {
        Event::dispatch($event);
    }
}
