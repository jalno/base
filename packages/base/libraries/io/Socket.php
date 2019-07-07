<?php
namespace packages\base\IO;
use \packages\base\log;
class Socket{
	const IPv4 = AF_INET;
	const IPv6 = AF_INET6;
	const UNIX = AF_UNIX;

	const STREAM = SOCK_STREAM;
	const DGRAM = SOCK_DGRAM;
	const SEQPACKET = SOCK_SEQPACKET;
	const RAW = SOCK_RAW;
	const RDM = SOCK_RDM;

	const TCP = SOL_TCP;
	const UDP = SOL_UDP;

	const TEXT = PHP_NORMAL_READ;
	const BINARY = PHP_BINARY_READ;

	protected $domain;
	protected $type;
	protected $protocol;
	protected $socket;
	protected $address;
	protected $port;
	protected $buffer;
	protected $maxUDPPacketSize = 1024*1024;
	protected $autoClose = true;
	public function __construct(int $domain = self::IPv4, int $type = self::STREAM, int $protocol = self::TCP){
		$log = log::getInstance();
		if($domain != 0){
			$this->domain = $domain;
			$this->type = $type;
			$this->protocol = $protocol;
			$log->debug("try to create a socket");
			$this->socket = socket_create($domain, $type, $protocol);
			if ($this->socket) {
				$log->reply("success");
			}else{
				$log->reply()->fatal('failed');
				throw new CreateSocketException(socket_strerror(socket_last_error($this->socket)));
			}
		}else{
			$log->debug("domain is zero, so just we create new instance of Socket");
		}
	}
	public function __destruct(){
		if($this->socket and $this->autoClose){
			$log = log::getInstance();
			$log->debug("try to close socket");
			socket_close($this->socket);
		}
	}
	public function bind(string $IP, int $port = 0){
		$log = log::getInstance();
		$log->debug("bind {$IP}:{$port} to this socket");
		$result = socket_bind($this->socket, $IP, $port);
		if($result){
			$log->debug("success");
		}else{
			$log->reply()->fatal('failed');
			throw new BindSocketException(socket_strerror(socket_last_error($this->socket)));
		}
	}
	public function listen(){
		$log = log::getInstance();
		if($this->protocol != self::TCP){
			$log->fatal("We cannot listen on Raw Sockets, instead directly use read() method");
			throw new RawListenSocketException();
		}
		$log->debug("listen on this socket");
		$result = socket_listen($this->socket);
		if($result){
			$log->debug("success");
		}else{
			$log->reply()->fatal('failed');
			throw new ListenSocketException(socket_strerror(socket_last_error($this->socket)));
		}
	}
	public function listenOn(string $IP, int $port = 0){
		$this->bind($IP, $port);
		if($this->protocol == self::TCP){
			$this->listen();
		}
	}
	public function waitForNewConnection(){
		$log = log::getInstance();
		if($this->protocol != self::UDP){
			$resource = socket_accept($this->socket);
			if($resource){
				$log->debug("got a new connection, create new instance of Socket");
				$socket = new Socket();
				$socket->socket = $resource;
				$log->debug("get peer address");
				if(socket_getpeername($resource, $socket->address, $socket->port)){
					$log->reply("Success");
				}else{
					$log->reply()->error("Failed");
				}
				return $socket; 
			}
		}else{
			$buffer = '';
			$address = null;
			$port = null;
			$bytes = socket_recvfrom($this->socket, $buffer, $this->maxUDPPacketSize, null, $address, $port);
			if($bytes){
				$socket = new Socket(0);
				$socket->socket = $this->socket;
				$socket->autoClose = false;
				$socket->domain = $this->domain;
				$socket->type = $this->type;
				$socket->protocol = $this->protocol;
				$socket->address = $address;
				$socket->port = $port;
				$socket->buffer = $buffer;
				return $socket;
			}
		}
	}
	public function read(int $length,int $type = self::BINARY){
		$log = log::getInstance();
		$log->debug("try to read {$length} bytes");
		$buffer = '';
		$bytes = 0;
		if($type == self::BINARY){
			if($this->protocol != self::UDP){
				$bytes = socket_recv($this->socket, $buffer, $length, MSG_DONTWAIT);
			}else{
				$buffer = $this->buffer;
				$bytes = strlen($buffer);
			}
		}
		if($bytes !== false){
			$log->reply("{$bytes} bytes read");
		}else{
			$log->reply()->error("failed");
		}
		
		return $buffer;
	}
	public function write(string $data):int{
		if($this->protocol != self::UDP){
			$bytes = socket_write($this->socket, $data);
		}else{
			$bytes = socket_sendto($this->socket, $data, strlen($data), null, $this->address, $this->port);
		}
		return $bytes;
	}
	public function setMaxUDPPacketSize(int $max){
		$this->maxUDPPacketSize = $max;
	}
	public function getAddress(){
		return $this->address;
	}
	public function getPort(){
		return $this->port;
	}
	public function getProtocol(): int {
		return $this->protocol;
	}
}