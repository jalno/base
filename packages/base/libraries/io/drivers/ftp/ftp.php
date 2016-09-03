<?php
namespace packages\base\IO\drivers;
use \packages\base\IO\drivers\ftp\CannectionException;
use \packages\base\IO\drivers\ftp\AuthException;
use \packages\base\IO\drivers\ftp\ChangeDirException;
use \packages\base\IO\drivers\ftp\NotReady;
class ftp{
	const BINARY = FTP_BINARY;
	const ASCII = FTP_ASCII;
	private $defaultOptions = array(
		'host' => '',
		'port' => 21,
		'username' => '',
		'password' => '',
		'passive' => true,
		'root' => '',
		'ssl' => false,
		'timeout' => 30
	);
	private $options;
	private $connection;
	private $ready = false;
	function __construct($userOptions){
		$this->options = array_replace($this->defaultOptions, $userOptions);
		if($this->options['host'] and $this->options['port']){
			if($this->connect()){
				if($this->options['username'] and $this->options['password']){
					if($this->login()){
						if($this->options['root']){
							if(!$this->chdir($options['root'])){
								throw new ChangeDirException($options['root']);
							}
						}
						$this->ready = true;
					}
				}
			}
		}
	}
	private function connect(){
		$function = $this->options['ssl'] ? 'ftp_ssl_connect' : 'ftp_connect';
		if($this->connection = $function($this->options['host'], $this->options['port'], $this->options['timeout'])){
			return true;
		}else{
			throw new CannectionException;
		}
	}
	private function login(){
		if(ftp_login($this->connection, $this->options['username'], $this->options['password'])){
			return true;
		}else{
			throw new AuthException;
		}
	}
	public function chdir($dir){
		if($this->ready){
			return @ftp_chdir($this->connection, $dir);
		}else{
			throw new NotReady();
		}
	}
	public function put($local, $remote, $mode = self::BINARY, $startpos = 0){
		if($this->ready){
			return ftp_put($this->connection, $remote, $local, $mode, $startpos);
		}else{
			throw new NotReady();
		}
	}
	public function get($remote,$local, $mode = self::BINARY, $startpos = 0){
		if($this->ready){
			return ftp_get($this->connection, $local, $remote, $mode, $startpos);
		}else{
			throw new NotReady();
		}
	}
	public function is_ready(){
		return $this->ready;
	}
}
