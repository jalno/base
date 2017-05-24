<?php
namespace packages\base\http;
use \packages\base\IO\file;
class request{
	private $method = 'GET';
	private $query = array();
	private $uri;
	private $scheme = 'http';
	private $ip;
	private $host;
	private $port = 80;
	private $headers = array();
	private $body = '';
	private $proxy;
	private $file;
	public function __construct(string $host,string $uri){
		$this->setHost($host);
		$this->setURI($uri);
	}
	public function setMethod(string $method){
		$this->method = strtoupper($method);
	}
	public function getMethod():string{
		return $this->method;
	}
	public function setHost(string $host){
		$this->host = $host;
	}
	public function getHost():string{
		return $this->host;
	}
	public function setURI(string $uri){
		while(substr($uri, 0, 1) == '/'){
			$uri = substr($uri, 1);
		}
		$this->uri = $uri;
	}
	public function getURI():string{
		return $this->uri;
	}
	public function setQuery(array $query){
		$this->query = $query;
	}
	public function getQuery():array{
		return $this->query;
	}
	public function getURL():string{
		$url = $this->scheme . '://'.$this->host;
		if($this->port){
			$url .= ':'.$this->port;
		}
		$url .= '/'.$this->uri;
		if($this->query){
			$url .= '?'.http_build_query($this->query);
		}
		return $url;
	}
	public function setScheme(string $scheme){
		$this->scheme = $scheme;
	}
	public function getScheme():string{
		return $this->scheme;
	}
	public function setPort(int $port){
		$this->port = $port;
	}
	public function getPort():int{
		return $this->port;
	}
	public function setIP(string $ip){
		$this->ip = $ip;
	}
	public function getIP():string{
		return $this->ip;
	}
	public function setReferer(string $referer){
		$this->setHeader('Referer', $referer);
	}
	public function getReferer():string{
		return $this->getHeader('Referer');
	}
	public function setHeader(string $name, string $value){
		$this->headers[$name] = $value;
	}
	public function getHeader(string $name):string{
		return isset($this->headers[$name]) ? $this->headers[$name] : null;
	}
	public function setHeaders(array $headers){
		foreach($headers as $name => $value){
			$this->setHeader($name, $value);
		}
	}
	public function getHeaders():array{
		return $this->headers;
	}
	public function setBody(string $body){
		$this->body = $body;
	}
	public function getBody():string{
		return $this->body;
	}
	public function setProxy(array $proxy){
		$this->proxy = $proxy;
	}
	public function getProxy(){
		return $this->proxy;
	}
	public function saveAs(file $file){
		$this->file = $file;
	}
	public function getSaveAs(){
		return $this->file;
	}
}