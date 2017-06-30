<?php
namespace packages\base\cache\memcache;
class MemcacheExtensionException extends \Exception{
	public function __construct(){
		parent::__construct("memcached extenstion doesn't loaded");
	}
}