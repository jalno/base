<?php
namespace packages\base\cache;
interface Ihandler{
	public function get(string $name);
	public function has(string $name):bool;
	public function set(string $name, $value, int $timeout);
	public function delete(string $name);
	public function flush();
	public function clear();
}