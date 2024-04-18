<?php

namespace packages\base;

use packages\base\Frontend\Theme;

class Events
{
    public static function trigger(EventInterface $event)
    {
        $log = Log::getInstance();
        $log->debug('trigger', get_class($event));
        foreach (Packages::get() as $package) {
            $package->trigger($event);
        }
        foreach (Theme::get() as $theme) {
            $theme->trigger($event);
        }
    }
}
