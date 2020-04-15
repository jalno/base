<?php
namespace packages\base;

use packages\base\Loader;
use packages\base\log\Instance;

class Log {
	const debug = 1;
	const info = 2;
	const warn = 3;
	const error = 4;
	const fatal = 6;
	const off = 0;
	static protected $file;
	static private $api;
	static private $parent;
	static private $generation = 0;
	static private $indentation = "\t";
	public static function newChild() {
		self::$generation++;
	}
	public static function dieChild() {
		self::$generation--;
	}
	public static function getParent() {
		if (!self::$parent) {
			self::$parent = self::getInstance();
		}
		return self::$parent;
	}
	public static function getInstance() {
		if (!self::$api) {
			self::$api = Loader::sapi();
		}
		$level = self::off;
		if (self::$parent) {
			$level = self::$parent->getLevel();
		}
		return new Instance($level);
	}
	public static function setFile($file) {
		self::$file = $file;
	}
	public static function setLevel($level) {
		switch(strtolower($level)) {
			case('debug'):$level = self::debug; break;
			case('info'):$level = self::info; break;
			case('warn'):$level = self::warn; break;
			case('error'):$level = self::error; break;
			case('fatal'):$level = self::fatal; break;
			case('off'):$level = self::off; break;
		}
		self::getParent()->setLevel($level);
	}
	public static function debug() {
		return call_user_func_array(array(self::getParent(),'debug'), func_get_args());
	}
	public static function info() {
		return call_user_func_array(array(self::getParent(),'info'), func_get_args());
	}
	public static function warn() {
		return call_user_func_array(array(self::getParent(),'warn'), func_get_args());
	}
	public static function error() {
		return call_user_func_array(array(self::getParent(),'error'), func_get_args());
	}
	public static function fatal() {
		return call_user_func_array(array(self::getParent(),'fatal'), func_get_args());
	}
	public static function append() {
		return call_user_func_array(array(self::getParent(),'append'), func_get_args());
	}
	public static function reply() {
		return call_user_func_array(array(self::getParent(),'reply'), func_get_args());
	}
	public static function setIndentation(string $indentation, int $repeat = 1) {
		self::$indentation = str_repeat($indentation,$repeat);
	}
	public static function write($level, $message) {
		$microtime = explode(" ", microtime());
		$date = date("Y-m-d H:i:s." . substr($microtime[0], 2) . " P");
		$pidText = (self::$api == Loader::cli ? (" [" . getmypid() . "] ") : " ");
		$coloredMessage = $message;
		$levelText = "";
		$coloredLevelText = "";
		switch($level) {
			case(self::debug):
				$levelText = "[DEBUG]";
				$coloredLevelText = "\033[46m" . $levelText . "\033[0m"; // Background Cyan
				$coloredMessage = "\033[96m" . $message . "\033[0m"; // Light Cyan
				break;
			case(self::info):
				$levelText = "[INFO]";
				$coloredLevelText = "\033[42m" . $levelText . "\033[0m"; // Background Green
				$coloredMessage = "\033[92m" . $message . "\033[0m"; // Light Green
				break;
			case(self::warn):
				$levelText = "[WARN]";
				$coloredLevelText = "\033[43m" . $levelText . "\033[0m"; // Background Yellow
				$coloredMessage = "\033[93m" . $message . "\033[0m"; // Light Yellow
				break;
			case(self::error):
				$levelText = "[ERROR]";
				$coloredLevelText = "\033[45m" . $levelText . "\033[0m"; // Background Magenta
				$coloredMessage = "\033[95m" . $message . "\033[0m"; // Light Magenta
				break;
			case(self::fatal):
				$levelText = "[FATAL]";
				$coloredLevelText = "\033[41m" . $levelText . "\033[0m"; // Background Red
				$coloredMessage = "\033[91m" . $message . "\033[0m"; // Light Red
				break;
		}
		$generation = (self::$generation > 1 ? str_repeat(self::$indentation, self::$generation-1) : " ");
		$coloredLine = $date . $pidText . $coloredLevelText . $generation . $coloredMessage . PHP_EOL;
		$line = $date . $pidText . $levelText . $generation . $message . PHP_EOL;
		if (Options::get("packages.base.logging.quiet", false) == 0) {
			if (self::$api == Loader::cli) {
				if (in_array($level, array(self::error, self::fatal))) {
					fwrite(STDERR, stream_isatty(STDERR) ? $coloredLine : $line);
				} else {
					echo(stream_isatty(STDOUT) ? $coloredLine : $line);
				}
			} else {
				echo $line;
			}
		}
		file_put_contents(self::$file, $line, is_file(self::$file) ? FILE_APPEND : 0);
	}
}
