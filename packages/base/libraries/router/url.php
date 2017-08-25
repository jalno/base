<?php
namespace packages\base;
use \packages\base\router;
use \packages\base\options;
use \packages\base\IO;
function url($page = '',$parameters = array(), $absolute = false){
	$lastSlash = options::get('packages.base.routing.lastslash');
	if($lastSlash == true){
		if(substr($page, -1) != '/'){
			$page .= '/';
		}
	}else{
		while(substr($page,-1) == '/'){
			$page = substr($page, 0, strlen($page) - 1);
		}
	}
	$encode = isset($parameters['@encode']) and $parameters['@encode'];
	if($encode){
		unset($parameters['@encode']);
	}
	$url = '';
	if($absolute){
		$hostname = '';
		if(isset($parameters['hostname'])){
			trigger_error("'hostname' parameter is deprecated, use '@hostname' instead", E_USER_DEPRECATED);
			$hostname = $parameters['hostname'];
			unset($parameters['hostname']);
		}elseif(isset($parameters['@hostname'])){
			$hostname = $parameters['@hostname'];
			unset($parameters['@hostname']);
		}else{
			$hostname = router::gethostname();
		}
		if(!$hostname and $defaultHostnames = router::getDefaultDomains()){
			$hostname = $defaultHostnames[0];
		}
		$url .= router::getscheme().'://'.$hostname;
	}

	$changelang = options::get('packages.base.translator.changelang');
	$type = options::get('packages.base.translator.changelang.type');
	if($changelang == 'uri'){
		$lang = '';
		if(isset($parameters['lang'])){
			trigger_error("'lang' parameter is deprecated, use '@lang' instead", E_USER_DEPRECATED);
			$lang = $parameters['lang'];
			unset($parameters['lang']);
		}elseif(isset($parameters['@lang'])){
			$lang = $parameters['@lang'];
			unset($parameters['@lang']);
		}else{
			if($type == 'short'){
				$lang = translator::getShortCodeLang();
			}elseif($type == 'complete'){
				$lang = translator::getCodeLang();
			}
		}
		if(!$page){
			if(strlen($lang) == 2){
				if($lang != translator::getDefaultShortLang()){
					$url .= '/'.$lang;
				}
			}elseif($lang and $lang != translator::getDefaultLang()){
				$url .= '/'.$lang;
			}
		}elseif($lang){
			$url .= '/'.$lang;
		}
	}elseif($changelang == 'parameter'){
		if(!isset($parameters['@lang'])){
			if($type == 'short'){
				$parameters['@lang'] = translator::getShortCodeLang();
			}elseif($type == 'complete'){
				$parameters['@lang'] = translator::getCodeLang();
			}
		}
	}else{
		unset($parameters['@lang'], $parameters['lang']);
	}
	if($page){
		if($encode){
			$page = str_replace('%2F', '/', urlencode($page));
		}
		$url .= '/'.$page;
	}
	if(!$url){
		$url .= '/';
	}
	if(is_array($parameters) and $parameters){
		$url .= '?'.http_build_query($parameters);
	}
	return $url;
}
