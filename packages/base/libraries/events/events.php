<?php
namespace packages\base;
class events{
	static public function trigger(event $event){
		$packages = packages::get();
		foreach($packages as $package){
			$package->trigger($event);
		}
	}
}
