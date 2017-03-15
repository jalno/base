<?php
namespace packages\base\session;
interface session_handler{
	const UNSETED = false;
	public function __construct($cookie,$ip);
	public function start($id = '');
	public function getID();
	public function set($key, $value);
	public function get($key);
	public function destroy();
}
