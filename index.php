<?php
namespace packages\base;
chdir(__DIR__);
if (!is_file("packages/base/libraries/config/config.php") or !is_readable("packages/base/libraries/config/config.php")){
	echo("FATAL ERROR\n");
}
require __DIR__ . "/vendor/autoload.php";
require_once("packages/base/libraries/config/config.php");
require_once("packages/base/libraries/loader/loader.php");

try {
	AutoLoader::setDefaultClassMap();
	AutoLoader::register();

	$api = Loader::sapi();
	if ($api == Loader::cgi) {
		HTTP::set();
	} elseif ($api == Loader::cli) {
		CLI::set();
	}

	Loader::options();
	if ($level = Options::get("packages.base.logging.level")) {
		Log::setLevel($level);
	}

	Log::debug("set 'root_directory' option to ", __DIR__);
	Options::set('root_directory', __DIR__);
	Log::info("loading packages");
	Loader::packages();
	Log::reply("Success");

	Session::autoStart();

	if ($api == Loader::cgi) {
		Log::info("loading themes");
		Loader::themes();
		Log::reply("Success");
	}
	Log::info("routing");
	Router::routing();
	Log::reply("Success");


} catch(\Throwable $e) {
	header("HTTP/1.0 500 Internal Server Error");
	if (Loader::isDebug()) {
		echo("Throwable: {$e}\n");
		print_r($e);
	} else {
		echo("An error occured, Please contact support team.");
	}
}
