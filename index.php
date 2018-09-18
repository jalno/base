<?php

error_reporting(E_ALL);
ini_set( 'display_startup_errors', 1 );
ini_set('display_errors', 1);
chdir(__DIR__);
use \packages\base\loader;
use \packages\base\router;
use \packages\base\http;
use \packages\base\cli;
use \packages\base\log;
use \packages\base\date;
use \packages\base\options;
if(is_file("packages/base/libraries/config/config.php") and is_readable("packages/base/libraries/config/config.php")){
	try{
		require_once("packages/base/libraries/config/config.php");
		require_once("packages/base/libraries/loader/loader.php");
		loader::register_autoloader();
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
		//base\frontend\theme::selectTheme();
		loader::autoStartSession();
		log::info("loading packages");
		loader::packages();
		log::reply("Success");
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

}else{
	echo("FATAL ERROR\n");
}
