<?php
namespace packages\base;

use packages\base\session\{ISessionHandler, StartSessionException, PHPSessionHandler, DbSessionHandler, CacheSessionHandler};


class Session {

	/**
	 * @var ISessionHandler|null
	 */
	private static $handler;

	/**
	 * @var array|null
	 */
	private static $options;

	/**
	 * Getter and initliazer for handler based on options.
	 * 
	 * @return ISessionHandler
	 */
	public static function getHandler(): ISessionHandler {
		if (!self::$handler) {
			$log = Log::getInstance();
			self::getOptions();
			$log->debug("initlizing session handler:", self::$options['handler']);
			self::$handler = new self::$options['handler'](self::$options);
		}
		return self::$handler;
	}

	/**
	 * Getter and initlizer for options.
	 * 
	 * @return array
	 */
	public static function getOptions(): array {
		if (!self::$options) {
			$log = Log::getInstance();
			$log->debug("initlizing session options");
			self::$options = Options::get("packages.base.session");
			if (!self::$options) {
				self::$options = [];
			}
			self::$options = array_replace_recursive(array(
				'handler' => PHPSessionHandler::class,
				'autostart' => false,
			), self::$options);

			switch (self::$options['handler']) {
				case "php":
					self::$options['handler'] = PHPSessionHandler::class;
					break;
				case "DB":
					self::$options['handler'] = DbSessionHandler::class;
					break;
				case "cache":
					self::$options['handler'] = CacheSessionHandler::class;
					break;
			}
		}
		return self::$options;
	}

	public static function autoStart(): void {
		if (Loader::sapi() != Loader::cgi) {
			return;
		}
		self::getOptions();
		if (self::$options['autostart']) {
			self::start();
		}
	}

	/**
	 * Start session and load it's handler
	 * 
	 * @throws Session\StartSessionException if cannot start the session
	 * @return void
	 */
	public static function start(): void {
		self::getHandler()->start();
	}

	/**
	 * Delete & destroy session from storage.
	 * 
	 * @return void
	 */
	public static function destroy(){
		self::getHandler()->destroy();
	}

	/**
	 * Set a key-value pair in memory.
	 * It's not necessarily commit the new data in time.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void 
	 */
	public static function set(string $key, $value): void {
		self::getHandler()->set($key, $value);
	}

	/**
	 * Get key's value from memory.
	 * It's retrive the data which cached since session started; that data may or may not changed by another process.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public static function get(string $key) {
		return self::getHandler()->get($key);
	}

	/**
	 * Unset a given key
	 * 
	 * @param string $key The key to be unset. 
	 * @return void
	 */
	public static function unset(string $key): void {
		self::getHandler()->unset($key);
	}

	/**
	 * Getter for session's ID.
	 * 
	 * @return string|null NULL if session isn't started yet.
	 */
	public static function getID(): string {
		return self::getHandler()->getID();
	}
}
