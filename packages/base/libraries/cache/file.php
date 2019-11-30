<?php
namespace packages\base\cache;

use packages\base\{packages, IO, date, log, file\LockTimeoutException};

class file implements Ihandler{
	private $options;
	private $storage;
	private $index;
	public function __construct($options){
		$log = log::getInstance();
		$this->options = $options;
		if(!isset($this->options['prefix'])){
			$this->options['prefix'] = '';
		}
		if(!isset($this->options['storage'])){
			$this->options['storage'] = realpath(__DIR__ . "/../../storage/private/cache");
		}
		$log->info("storage is ", $this->options['storage']);
		$this->storage = is_string($this->options['storage']) ? new IO\directory\local($this->options['storage']) : $this->options['storage'];
		if(!$this->storage->exists()){
			$log->info("storage does not exists, creating it");
			$this->storage->make(true);
		}
	}
	public function get(string $name){
		$item = $this->item($name);
		if(!$item->exists()){
			log::getInstance()->debug("item does not exists");
			return null;
		}
		return unserialize($item->read());
	}
	public function has(string $name):bool{
		return $this->item($name)->exists();
	}
	public function set(string $name, $value, int $timeout){
		$item = $this->item($name);
		$this->setIndex($item, $timeout > 0 ? date::time() + $timeout : 0);
		$item->write(serialize($value));
	}
	public function delete(string $name){
		$item = $this->item($name);
		$this->removeIndex($item);
		if($this->item($name)->exists()){
			$item->delete();
		}
	}
	public function flush(){
		$log = log::getInstance();
		$log->debug("flush index");
		$this->writeIndex([]);
		foreach($this->storage->files(false) as $file){
			if($file->basename != 'index' and $file->basename != 'lock'){
				$log->debug("delete {$file->basename}");
				$file->delete();
			}
		}
	}
	public function clear(){
		$items = $this->readIndex();
		foreach($items as $x => $index){
			if($index[2] and $index[2] < date::time()){
				$item = $this->storage->file($index[0]);
				if($item->exists()){
					$item->delete();
				}
				unset($items[$x]);
			}
		}
		$this->writeIndex($items);
	}
	private function item(string $name){
		$md5 = md5($this->options['prefix'].$name);
		return $this->storage->file($md5);
	}
	private function lockIndex(){
		$startAt = date::time();
		$lock = $this->storage->file('index.lock');
		while($lock->exists() and date::time() - $startAt < 10);
		$lock->write("");
		return $lock;
	}
	private function readIndex():array{
		$index = $this->storage->file('index');
		if(!$index->exists()){
			return [];
		}
		$keys = [];
		$buffer = $index->open(IO\file\local::readOnly);
		while($line = $buffer->readLine()){
			$line = explode(",", $line, 3);
			$line[1] = intval($line[1]);
			$line[2] = intval($line[2]);
			$keys[] = $line;
		}
		return $keys;
	}
	private function writeIndex(array & $items){
		$lock = $this->lockIndex();
		$index = $this->storage->file('index');
		$buffer = $index->open(IO\file\local::writeOnly);
		foreach($items as $item){
			$buffer->write(implode(",", $item)."\n");
		}
		$lock->delete();
	}
	private function setIndex(IO\file $item, int $expire){
		$items = $this->readIndex();
		$keys = array_column($items, 0);
		$index = array_search($item->basename, $keys);
		if($index !== false){
			$items[$index][2] = $expire;
			$this->writeIndex($items);
		}else{
			$item = [$item->basename, date::time(), $expire];
			$items[] = $item;
			$lock = $this->lockIndex();
			$index = $this->storage->file('index');
			$index->append(implode(",", $item)."\n");
			$lock->delete();
		}
	}
	private function removeIndex(IO\file $item){
		$items = $this->readIndex();
		$keys = array_column($items, 0);
		$index = array_search($item->basename, $keys);
		if($index !== false){
			unset($items[$index]);
			$this->writeIndex($items);
		}
	}
}