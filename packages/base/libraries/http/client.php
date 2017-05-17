<?php
namespace packages\base\http;
use \packages\base\json;
use \packages\base\http\curl;
class client{
	private static $defaultOptions = array(
		'base_uri' => null,
		'allow_redirects' => true,
		'auth' => null,
		'body' => '',
		'cookies' => true,
		'connect_timeout' => 0,
		'debug' => false,
		'delay' => 0,
		'form_params' => null,
		'headers' => array(),
		'http_errors' => true,
		'json' => null,
		'multipart' => null,
		'proxy' => null,
		'query' => null,
		'ssl_verify' => true,
		'timeout' => 0
	);
	private $options;
	public function __construct(array $options = array()){
		$this->options = array_replace_recursive(self::$defaultOptions, $options);

	}
	public function request(string $method, string $URI, array $options = array()):response{
		$thisOptions = array_replace($this->options, $options);
		if($thisOptions['auth']){
			if(!isset($thisOptions['headers']['authorization'])){
				if(is_array($thisOptions['auth'])){
					if(isset($thisOptions['auth']['username'])){
						if(isset($thisOptions['auth']['password'])){
							$thisOptions['headers']['authorization'] = 'Basic '.base64_encode($thisOptions['auth']['username'].':'.$thisOptions['auth']['password']);
						}
					}
				}else{
					$thisOptions['headers']['authorization'] = $thisOptions['auth'];
				}
			}
		}
		if($thisOptions['json']){
			$thisOptions['headers']['content-type'] = 'application/javascript; charset=UTF-8';
			if(!$thisOptions['body']){
				$thisOptions['body'] = json\encode($thisOptions['json']);
			}
		}
		if($thisOptions['form_params']){
			$thisOptions['headers']['content-type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			if(!$thisOptions['body']){
				$thisOptions['body'] = http_build_query($thisOptions['form_params']);
			}
		}
		if($thisOptions['multipart']){
			$thisOptions['headers']['content-type'] = 'multipart/form-data; charset=UTF-8';
			if(!$thisOptions['body']){
				$thisOptions['body'] = $thisOptions['multipart'];
			}
		}
		if(preg_match("/^[a-z]+\:\/\//i", $URI)){
			$url = $URI;
		}else{
			$url = $thisOptions['base_uri'].$URI;
		}
		$url_parse = parse_url($url);
		if(!isset($url_parse['path'])){
			$url_parse['path'] = '';
		}
		$request = new request($url_parse['host'], $url_parse['path']);
		if(isset($url_parse['scheme'])){
			$request->setScheme($url_parse['scheme']);
		}
		if(isset($url_parse['port'])){
			$request->setPort($url_parse['port']);
		}
		$request->setMethod($method);
		if(isset($url_parse['query']) and $url_parse['query']){
			parse_str($url_parse['query'], $query);
			$thisOptions['query'] = array_replace_recursive($query, $thisOptions['query']);
		}
		if($thisOptions['query']){
			$request->setQuery($thisOptions['query']);
		}
		$request->setBody($thisOptions['body']);
		if($thisOptions['delay'] > 0){
			usleep($thisOptions['delay']);
		}
		if(is_array($thisOptions['headers'])){
			$request->setHeaders($thisOptions['headers']);
		}
		$handler = new curl();
		$response = $handler->fire($request, $thisOptions);
		
		$status = $response->getStatusCode();
		if($status >= 400 and $status < 500){
			throw new clientException($request, $response);
		}elseif($status >= 500 and $status < 600){
			throw new serverException($request, $response);
		}
		return $response;
	}
	public function get(string $URI, array $options = array()):response{
		return $this->request('get', $URI, $options);
	}
	public function post(string $URI, array $options = array()):response{
		return $this->request('post', $URI, $options);
	}
}