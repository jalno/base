<?php
namespace packages\base;
trait AutoloadContainerTrait {
	
	/** @var packages\base\IO\file|array|null */
	private $autoload;


	/**
	 * Set autoloader rules by passing a file name or rules themselfs.
	 * 
	 * @param string|array $autoload
	 * @throws packages\base\IO\NotFoundException if autoload was a string and there isn't any with this name in package home directory.
	 * @return void
	 */
	public function setAutoload($autoload): void {
		if (is_array($autoload)) {
			$this->autoload = $autoload;
			return;
		}
		$file = $this->home->file($autoload);
		if (!$file->exists()) {
			throw new IO\NotFoundException($file);
		}
		$this->autoload = $file;
	}

	/**
	 * Get the autoload
	 * 
	 * @return packages\base\IO\file|array|null
	 */
	public function getAutoload() {
		return $this->autoload;
	}
	/**
	 * Return list of all autoloaded files.
	 * 
	 * @throws packages\base\IO\NotFoundException if cannot find any file in autoloader.
	 * @return array
	 */
	public function getAutoloadFiles(): array {
		$rules = $this->getAutoloadRules();
		if (!$rules) {
			return [];
		}
		$files = [];
		$paths = [];
		if (isset($rules['files'])) {
			foreach ($rules['files'] as $rule) {
				$file = $this->home->file($rule['file']);
				if (!$file->exists()) {
					throw new IO\NotFoundException($file);
				}
				$path = $file->getPath();
				if (in_array($path, $paths)) {
					continue;
				}
				$paths[] = $path;
				$files[] = $file;
			}
		}
		if (isset($rules['directories'])) {
			foreach ($rules['directories'] as $path) {
				$directory = $this->home->directory($path);
				foreach($directory->files(true) as $file) {
					if ($file->getExtension() != "php") {
						continue;
					}
					$fpath = $file->getPath();
					if (in_array($fpath, $paths)) {
						continue;
					}
					$paths[] = $path;
					$files[] = $file;
				}
			}
		}
		return $files;
	}

	/**
	 * 
	 * @throws packages\base\json\JsonException {@see json\decode()}
	 * @throws packages\base\PackageConfigException if autoload does have files or directories indexes.
	 * @return array|null
	 */
	public function getAutoloadRules(): ?array {
		if (!$this->autoload) {
			return null;
		}
		$autoload = is_array($this->autoload) ? $this->autoload : json\decode($this->autoload->read());
		if (!isset($autoload['files']) and !isset($autoload['directories'])) {
			throw new PackageConfigException($this->name, "autoload does have files or directories indexes.");
		}
		return $autoload;
	}

}