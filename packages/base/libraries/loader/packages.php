<?php
namespace packages\base;

class packages {
	/** @var package[] */
	static private $actives = [];
	
	/**
	 * Register a new package
	 * 
	 * @param package
	 * @return void
	 */
	static function register(package $package): void {
		self::$actives[$package->getName()] = $package;
	}

	/**
	 * Return package by search its name
	 * 
	 * @param string $name
	 * @return package|null
	 */
	static function package(string $name): ?package {
		return self::$actives[$name] ?? null;
	}

	/**
	 * get list of active packages.
	 * 
	 * @param string[] $names
	 * @return Package[]
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
