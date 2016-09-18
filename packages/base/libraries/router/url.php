<?php
namespace packages\base;
use \packages\base\http;
function url($page = '',$parameters = array(), $absolute = false){
	return ($absolute ? http::$request['scheme'].'://'.http::$request['hostname'] : '')."/".$page.($parameters ? '?'.http_build_query($parameters) : '');
}
