<?php
namespace packages\base;
if (!is_file("config/config.php") or !is_readable("config/config.php")){
	echo("FATAL ERROR\n");
}

require_once("config/config.php");
require_once(__DIR__ . "/libraries/loader/loader.php");

try {
	AutoLoader::setDefaultClassMap();
	AutoLoader::register();

	$api = Loader::sapi();
	if ($api == Loader::cgi) {
		Http::set();
	} elseif ($api == Loader::cli) {
		Cli::set();
	}

	Loader::options();
	if ($level = Options::get("packages.base.logging.level")) {
		Log::setLevel($level);
	}

	Log::debug("set 'root_directory' option to ", getcwd());
	Options::set('root_directory', getcwd());
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
