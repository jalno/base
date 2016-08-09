<?php
namespace packages\base\session;
class php implements session_handler{
	private $cookie;
	private $ip;
	public function __construct($cookie,$ip){
		$this->cookie = $cookie;
		$this->ip = $ip;
	}
	public function start($id = ''){
		if(
			isset($this->cookie['expire']) or
			isset($this->cookie['path']) or
			isset($this->cookie['domain']) or
			isset($this->cookie['sslonly']) or
			isset($this->cookie['httponly'])
		){
			$defaultparams = session_get_cookie_params();
			if(!isset($this->cookie['expire']))$this->cookie['expire'] = $defaultparams['lifetime'];
			if(!isset($this->cookie['path']))$this->cookie['path'] = $defaultparams['path'];
			if(!isset($this->cookie['domain']))$this->cookie['domain'] = $defaultparams['domain'];
			if(!isset($this->cookie['sslonly']))$this->cookie['sslonly'] = $defaultparams['secure'];
			if(!isset($this->cookie['httponly']))$this->cookie['httponly'] = $defaultparams['httponly'];
			session_set_cookie_params($this->cookie['expire'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['sslonly'], $this->cookie['httponly']);
		}
		if(isset($this->cookie['name'])){
			session_name($this->cookie['name']);
		}
		if($id){
			session_id($id);
		}
		$start = session_start();
		if($start and (($this->ip and $this->checkIP()) or !$this->ip)){
			return true;
		}
		return false;
	}
	private function checkIP(){
		if(($ip = $this->get('SESSION_IP')) !== self::UNSETED){
			return($ip == \packages\base\http::$client['ip']);
		}else{
			$this->set('SESSION_IP', \packages\base\http::$client['ip']);
			return true;
		}
	}
	public function get($key){
		return isset($_SESSION[$key]) ? $_SESSION[$key] : self::UNSETED;
	}
	public function set($key, $value){
		if($value == self::UNSETED){
			unset($_SESSION[$key]);
		}else{
			$_SESSION[$key] = $value;
		}
		return true;
	}
}
?>
