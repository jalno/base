<?php
namespace packages\base\cache;
use \Memcached;
use \packages\base\date;
use \packages\base\db;
class database implements Ihandler{
	private $options;
	private $connection;
	public function __construct($options){
		$this->options = $options;
		if(!isset($this->options['prefix'])){
			$this->options['prefix'] = '';
		}
	}
	public function get(string $name){
		$connection = db::where("name", $this->name($name));
		$value = db::getValue("base_cache", "value");
		return $connection->count ? ununserialize($value) : null;
	}
	public function has(string $name):bool{
		db::where("name", $this->name($name));
		return db::has("base_cache");
	}
	public function set(string $name, $value, int $timeout = 0){
		if($this->has($name)){
			db::where("name", $this->name($name));
			db::update("base_cache", array(
				'value' => serialize($value),
				'expire_at' => date::time() + $timeout
			));
		}else{
			db::insert("base_cache", array(
				'name' => $this->name($name),
				'value' => serialize($value),
				'expire_at' => date::time() + $timeout
			));
		}
	}
	public function delete(string $name){
		db::where("name", $this->name($name));
		db::delete("base_cache");
	}
	public function flush(){
		db::delete("base_cache");
	}
	public function clear(){
		db::where("expire_at", date::time(), '<=');
		db::delete("base_cache");
	}
	public function getAllKeys(){
		return array_column(db::get("base_cache", null, ['name']), 'name');
	}
	private function name(string $name):string{
		return md5($this->options['prefix'].$name);
	}
}