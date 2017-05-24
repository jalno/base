<?php
namespace packages\base\http;
use \packages\base\http\handler;
use \packages\base\http\serverException;
use \packages\base\http\clientException;
class curl implements handler{
	public function fire(request $request, array $options):response{
		$ch = curl_init( $request->getURL());
		$fh = null;
		$header = '';
		$body = '';

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getBody());
		if(isset($options['timeout']) and $options['timeout'] > 0){
			curl_setopt($ch, CURLOPT_TIMEOUT_MS, $options['timeout'] * 1000000);
		}
		if(isset($options['connent_timeout']) and $options['connect_timeout'] > 0){
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $options['connent_timeout'] * 1000000);
		}
		if(isset($options['allow_redirects'])){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $options['allow_redirects']);
		}
		if(isset($options['cookies'])){
			curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookies']);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookies']);
		}
		if(isset($options['ssl_verify'])){
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,$options['ssl_verify']);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,$options['ssl_verify'] ? 2 : 0);
		}
		if(isset($options['proxy'])){
			curl_setopt($ch, CURLOPT_PROXY, $options['proxy']['hostname'].":".$options['proxy']['port']);
			if(isset($options['proxy']['username'], $options['proxy']['password']) and $options['proxy']['username']){
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['proxy']['username'].':'.$options['proxy']['password']);
			}
		}
		$headers = array();
		foreach($request->getHeaders() as $name => $value){
			$headers[] = $name.': '.$value;
		}
		if(!empty($headers)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if(isset($options['save_as'])){
			$fh = fopen($options['save_as']->getPath(), 'w');
			$waitForHeader = true;
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use(&$waitForHeader, &$body, &$header, $fh){
				if($waitForHeader){
					$body .= $data;
					if(strpos($body, "\r\n\r\n") !== false){
						list($header, $body) = explode("\r\n\r\n", $body, 2);
						$waitForHeader = false;
					}elseif(strlen($body) > 10240){
						$waitForHeader = false;
					}
					if(!$waitForHeader and $body){
						fwrite($fh, $body);
						$body = '';
					}
				}else{
					return fwrite($fh, $data);
				}
				return strlen($data);
			});
		}
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if($fh){
			if(isset($options['save_as']) and $body){
				fwrite($fh, $body);
				$body = '';
			}
			fclose($fh);
		}
		if(!isset($options['save_as'])){
			if(strpos($result, "\r\n\r\n") !== false){
				list($header, $body) = explode("\r\n\r\n", $result, 2);
			}else{
				$body = $result;
			}
		}
		$header = $this->decodeHeader($header);
		$response = new response($info['http_code'], $header);
		if(isset($options['save_as'])){
			$response->setFile($options['save_as']);
		}else{
			$response->setBody($body);
		}
		return $response;
	}
	private function decodeHeader(string $header):array{
		$result = array();
		$lines = explode("\r\n", $header);
		$length = count($lines);
		for($x = 1;$x < $length;$x++){
			$line = explode(":", $lines[$x], 2);
			$result[$line[0]] = isset($line[1]) ? ltrim($line[1]) : '';
		}
		return $result;
	}
}