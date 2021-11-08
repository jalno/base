<?php
namespace packages\base\cache;

use packages\base\{IO, Date};

class FileCachehandler implements ICachehandler {

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var IO\directory
	 */
	protected $storage;

	/**
	 * @var IO\File
	 */
	protected $index;

	/**
	 * Create a cache instance
	 * 
	 * @param array $options
	 */
	public function __construct(array $options) {
		$this->options = array_replace_recursive(array(
			'prefix' => '',
			'storage' => __DIR__ . "/../../storage/private/cache",
		), $options);

		$this->storage = is_string($this->options['storage']) ? new IO\directory\local($this->options['storage']) : $this->options['storage'];
		if(!$this->storage->exists()){
			$this->storage->make(true);
		}
	}

	/**
	 * Retrieve an item
	 * 
	 * @param string $name The key of the item to retrieve.
	 * @return mixed|null Returns the value stored in the cache or NULL otherwise.
	 */
	
	public function get(string $name) {
		$item = $this->item($name);
		if (!$item->exists()) {
			return null;
		}
		return unserialize($item->read());
	}

	/**
	 * Check existance of an item.
	 * @param string $name The key of the item to be check.
	 * @return bool
	 */
	public function has(string $name): bool {
		return $this->item($name)->exists();
	}

	/**
	 * Store an item
	 * 
	 * @param string $name The key under which to store the value. 
	 * @param mixed $value The value to store. 
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function set(string $name, $value, int $timeout): void {
		$item = $this->item($name);
		$this->setIndex($item, $timeout > 0 ? date::time() + $timeout : 0);
		$item->write(serialize($value));
	}

	/**
	 * Delete an item
	 * 
	 * @param string $name The key to be deleted.
	 * @return void
	 */
	public function delete(string $name): void {
		$item = $this->item($name);
		$this->removeIndex($item);
		if($this->item($name)->exists()){
			$item->delete();
		}
	}

	/**
	 * Invalidate all items in the cache.
	 * 
	 * @return void
	 */
	public function flush(): void {
		$this->writeIndex([]);
		foreach ($this->storage->files(false) as $file) {
			if ($file->basename != 'index' and $file->basename != 'lock') {
				$file->delete();
			}
		}
	}

	/**
	 * Run garbage collector for cache storage.
	 * 
	 * @return void
	 */
	public function clear(): void {
		$items = $this->readIndex();
		foreach ($items as $x => $index) {
			if ($index[2] and $index[2] < Date::time()) {
				$item = $this->storage->file($index[0]);
				if ($item->exists()) {
					$item->delete();
				}
				unset($items[$x]);
			}
		}
		$this->writeIndex($items);
	}

	/**
	 * Set a new expiration on an item
	 * 
	 * @param string $name The key under which to store the value.
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function touch(string $name, int $timeout): void {
		$item = $this->item($name);
		if (!$item->exists()) {
			return;
		}
		$this->setIndex($item, $timeout > 0 ? date::time() + $timeout : 0);
	}

	/**
	 * Get a file from storage for item's name.
	 * 
	 * @return IO\File
	 */
	protected function item(string $name): IO\File {
		$md5 = md5($this->options['prefix'] . $name);
		return $this->storage->file($md5);
	}

	/**
	 * Lock index file by creating anthor file
	 * 
	 * @return IO\File lock file
	 */
	protected function lockIndex(): IO\File {
		$startAt = Date::time();
		$lock = $this->storage->file('index.lock');
		while ($lock->exists() and date::time() - $startAt < 10);
		$lock->write("");
		return $lock;
	}

	/**
	 * Read and parser index file.
	 * 
	 * @return array
	 */
	protected function readIndex(): array {
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

	/**
	 * Write index file
	 * 
	 * @param array $items new items
	 * @return void
	 */
	protected function writeIndex(array &$items): void {
		$lock = $this->lockIndex();
		$index = $this->storage->file('index');
		$buffer = $index->open(IO\File\Local::writeOnly);
		foreach($items as $item){
			$buffer->write(implode(",", $item)."\n");
		}
		$lock->delete();
	}

	/**
	 * Set a file and it's expiration time to index file.
	 * 
	 * @param IO\File $item
	 * @param int $expire expiration time
	 * @return void
	 */
	protected function setIndex(IO\File $item, int $expire): void {
		$items = $this->readIndex();
		$keys = array_column($items, 0);
		$index = array_search($item->basename, $keys);
		if ($index !== false) {
			$items[$index][2] = $expire;
			$this->writeIndex($items);
		} else {
			$item = [$item->basename, date::time(), $expire];
			$items[] = $item;
			$lock = $this->lockIndex();
			$index = $this->storage->file('index');
			$index->append(implode(",", $item) . "\n");
			$lock->delete();
		}
	}

	/**
	 * Remove an item from index file.
	 * 
	 * @param IO\File $item
	 * @return void
	 */
	protected function removeIndex(IO\File $item): void {
		$items = $this->readIndex();
		$keys = array_column($items, 0);
		$index = array_search($item->basename, $keys);
		if ($index !== false) {
			unset($items[$index]);
			$this->writeIndex($items);
		}
	}
}