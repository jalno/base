<?php
namespace packages\base\cache;
use \Memcached;
use \packages\base\date;
use \packages\base\cache\memcache\MemcacheExtensionException;
use \packages\base\cache\memcache\ServerException;
class memcache implements Ihandler{
	private $options;
	private $memcache;
	public function __construct($options){
		if(!extension_loaded('memcached')){
			throw new MemcacheExtensionException;
		}
		$this->options = $options;
		if(!isset($this->options['prefix'])){
			$this->options['prefix'] = '';
		}
		$this->memcache = new Memcached(isset($this->options['persistentID']) ? $this->options['persistentID'] : null);
		if(!isset($this->options['server'])){
			$this->options['server'] = [array(
				'host' => 'localhost',
				'port' => 11211	
			)];
		}elseif(is_string($this->options['server'])){
			list($host, $port) = explode(":", $this->options['server'], 2);
			$this->options['server'] = [array(
				'host' => $host,
				'port' => $port	
			)];
		}elseif(is_array($this->options['server'])){
			foreach($this->options['server'] as $x => $server){
				if(is_string($server)){
					list($host, $port) = explode(":", $server, 2);
					$this->options['server'][$x] = array(
						'host' => $host,
						'port' => $port	
					);
				}
			}
		}
		foreach($this->options['server'] as $server){
			if(!isset($server['host'])){
				throw new ServerException("host is wrong");
			}
			if(!isset($server['port'])){
				$server['port'] = 11211;
			}
			if(!isset($server['weight'])){
				$server['weight'] = 0;
			}
			$this->addServer($server['host'], $server['port'], $server['weight']);
		}
	}
	private function addServer(string $host , int $port = 11211, int $weight = 0){
		if(!$host){
			throw new ServerException("host is wrong");
		}
		if($port < 1 or $port >= 65536){
			throw new ServerException("port is wrong");
		}
		$this->memcache->addServer($host, $port, $weight);
	}
	public function get(string $name){
		return $this->memcache->get($this->name($name));
	}
	public function has(string $name):bool{
		return (bool)$this->get($name);
	}
	public function set(string $name, $value, int $timeout = 0){
		$this->memcache->set($this->name($name), $value, $timeout == 0 ? $timeout : date::time() + $timeout);
	}
	public function delete(string $name){
		$this->memcache->delete(self::name($name));
	}
	public function flush(){
		$this->memcache->flush();
	}
	public function clear(){
	}
	public function getAllKeys(){
		return $this->memcache->getAllKeys();
	}
	private function name(string $name):string{
		return md5($this->options['prefix'].$name);
	}
}