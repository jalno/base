<?php

namespace packages\base;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Event as LaravelEvent;

class Event implements EventInterface
{
    use Dispatchable;

    public function trigger()
    {
        LaravelEvent::dispatch($this);
    }
}
