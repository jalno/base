<?php
namespace packages\base;

class packages {
	/** @var packages\base\package[] */
	static private $actives = [];
	
	/**
	 * Register a new package
	 * 
	 * @param packages\base\package
	 * @return void
	 */
	static function register(package $package): void {
		self::$actives[$package->getName()] = $package;
	}

	/**
	 * Return package by search its name
	 * 
	 * @param string $name
	 * @return packages\base\package|null
	 */
	static function package(string $name): ?package {
		return self::$actives[$name] ?? null;
	}

	/**
	 * get list of active packages.
	 * 
	 * @param string[] $names
	 * @return packages\base\package[]
	 */
	static function get($names = []): array {
		if (empty($names)) {
			return self::$actives;
		}
		$return = array();
		foreach(self::$actives as $name => $package){
			if(in_array($name, $names)){
				$return[] = $package;
			}
		}
		return $return;
	}

	public static function registerTranslates(string $code) {
		foreach (self::$actives as $package) {
			$package->registerTranslates($code);
		}
	}
}
