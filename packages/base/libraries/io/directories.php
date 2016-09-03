<?php
namespace packages\base\IO;
function mkdir($pathname, $recursive = false, $mode = 0755){
	return \mkdir($pathname, $mode, $recursive);
}
function removeLastSlash($path){
	while(substr($path,-1) == '/'){
		$path = substr($path,0, strlen($path)-1);
	}
	return $path;
}
