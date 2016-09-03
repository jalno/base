<?php

error_reporting(E_ALL);
ini_set( 'display_startup_errors', 1 );
ini_set('display_errors', 1);
use \packages\base\loader;
use \packages\base\router;
use \packages\base\http;
use \packages\base\cli;
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
		options::set('root_directory', __DIR__);
		//base\frontend\theme::selectTheme();
		loader::packages();

		if($api == loader::cgi){
			loader::themes();
		}
		router::routing();

	}catch(Exception $e){
		echo("exception: {$e}<br>\n");
		print_r($e);
	}

}else{
	echo("FATAL ERROR\n");
}
