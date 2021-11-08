<?php
namespace packages\base;
use \packages\base\frontend\theme;
class events{
	static public function trigger(EventInterface $event){
		$log = log::getInstance();
		$log->debug("trigger", get_class($event));
		foreach(packages::get() as $package){
			$package->trigger($event);
		}
		foreach(theme::get() as $theme){
			$theme->trigger($event);
		}
	}
}
