<?php

namespace packages\base;

class Event implements EventInterface
{
    public function trigger()
    {
        Events::trigger($this);
    }
}
