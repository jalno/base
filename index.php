<?php
namespace packages\base;
chdir(__DIR__);
if (!is_file("packages/base/libraries/config/config.php") or !is_readable("packages/base/libraries/config/config.php")){
	echo("FATAL ERROR\n");
}

require_once("packages/base/libraries/config/config.php");
require_once("packages/base/libraries/loader/loader.php");

try{
	Autoloader::setDefaultClassMap();
	Autoloader::register();

	$api = loader::sapi();
	if($api == loader::cgi){
		http::set();
	}elseif($api == loader::cli){
		cli::set();
	}

	loader::options();
	log::setFile("packages/base/storage/protected/logs/".date("Y-m-d").".log");
	if($level = options::get("packages.base.logging.level")){
		log::setLevel($level);
	}
	log::debug("set 'root_directory' option to ", __DIR__);
	options::set('root_directory', __DIR__);
	log::info("loading packages");
	loader::packages();
	log::reply("Success");

	Session::autoStart();

	if($api == loader::cgi){
		log::info("loading themes");
		loader::themes();
		log::reply("Success");
	}
	log::info("routing");
	router::routing();
	log::reply("Success");

}catch(Exception $e){
	echo("exception: {$e}<br>\n");
	print_r($e);
}
