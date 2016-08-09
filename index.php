<?php

error_reporting(E_ALL);
ini_set( 'display_startup_errors', 1 );
ini_set('display_errors', 1);
use \packages\base;
if(is_file("packages/base/libraries/config/config.php") and is_readable("packages/base/libraries/config/config.php")){
	try{
		require_once("packages/base/libraries/config/config.php");
		require_once("packages/base/libraries/loader/loader.php");
		base\loader::register_autoloader();
		base\http::set();
		base\loader::options();
		base\frontend\theme::selectTheme();
		base\loader::packages();
		base\router::routing();
	}catch(Exception $e){
		echo("exception: {$e}<br>\n");
		print_r($e);
	}

}else{
	echo("FATAL ERROR\n");
}
