<?php
namespace packages\base\session;
use \packages\base;
use \packages\base\IO;
use \packages\base\http;
class file implements session_handler{
	private $cookie;
	private $ip;
	private $data;
	private $storage;
	private $file;
	public function __construct($cookie,$ip){
		$this->cookie = $cookie;
		$this->ip = $ip;
		$this->storage = __DIR__.'/../../storage/session';
		if(!is_dir($this->storage)){
			IO\mkdir($this->storage, true);
		}
	}
	public function start($id = ''){
		if(!isset($this->cookie['name']))$this->cookie['name'] = 'SESSID';
		$start = false;
		if(
			isset(http::$request['cookies'][$this->cookie['name']]) and
			preg_match('/^[0-9a-f]{32}$/', http::$request['cookies'][$this->cookie['name']]) and
			$this->load(http::$request['cookies'][$this->cookie['name']])
		){
			$start = true;
		}else{
			if(!$id)
				$id = $this->genID();
			if(!isset($this->cookie['expire']))$this->cookie['expire'] = 3600;
			if(!isset($this->cookie['path']))$this->cookie['path'] = '/';
			if(!isset($this->cookie['domain']))$this->cookie['domain'] = '';
			if(!isset($this->cookie['sslonly']))$this->cookie['sslonly'] = false;
			if(!isset($this->cookie['httponly']))$this->cookie['httponly'] = false;
			$start = $this->register($id);
		}
		if($start and (($this->ip and $this->checkIP()) or !$this->ip)){
			return true;
		}
		return false;
	}
	private function genID(){
		$ip= http::$client['ip'];
		$id = md5(rand(100, 1000).$ip.rand(100, 1000).rand(100, 1000));
		while(!is_file($this->storage.'/'.$id)){
			return $id;
		}
	}
	private function load($id){
		$file = $this->storage.'/'.$id;
		if(is_file($file) and is_readable($file)){
			$this->file = $file;
			$this->data = unserialize(file_get_contents($file));
			return true;
		}
		return false;
	}
	private function register($id){
		$cookie = http::setcookie($this->cookie['name'], $id, $this->cookie['expire'] > 0 ? time()+$this->cookie['expire'] : $this->cookie['expire'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['sslonly'], $this->cookie['httponly']);
		if($cookie){
			$file = $this->storage.'/'.$id;
			$this->data = array();
			$data = serialize($this->data);
			if(file_put_contents($file, $data,  LOCK_EX) == $data){
				$this->file = $this->storage.'/'.$id;
				return true;
			}
		}
		return false;
	}
	private function checkIP(){
		if(($ip = $this->get('SESSION_IP')) !== self::UNSETED){
			return($ip == http::$client['ip']);
		}else{
			$this->set('SESSION_IP', http::$client['ip']);
			return true;
		}
	}
	public function get($key){
		return isset($this->data[$key]) ? $this->data[$key] : self::UNSETED;
	}
	public function set($key, $value){
		if($value == self::UNSETED){
			unset($this->data[$key]);
		}else{
			$this->data[$key] = $value;
		}
		$data = serialize($this->data);
		return (@file_put_contents($this->file, $data,  LOCK_EX) == $data);
	}
}
