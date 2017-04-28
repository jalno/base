<?php
namespace packages\base;
use \packages\base\ssh\ConnectionException;
class ssh{
    private $connection = null;
    private $sftp = null;
    public $connectd = false;
    private $host;
    private $port;
    private $username;
    private $password;
    function __construct(string $host, int $port){
        if(($this->connection = @ssh2_connect($host, $port)) === false){
            throw new ConnectionException;
        }
        $this->host = $host;
        $this->port = $port;
    }
	function AuthByPassword(string $username,string $password):bool{
		if(@ssh2_auth_password($this->connection, $username, $password)){
            $this->username = $username;
            $this->password = $password;
            return true;
        }
        return false;
	}
	public function connection(){
		return $this->connection;
	}
    public function execute($comment){
        $str  = ssh2_exec($this->connection,$comment);
        $errstr = ssh2_fetch_stream($str, SSH2_STREAM_STDERR);
        stream_set_blocking($str, true);
        stream_set_blocking($errstr, true);
        $output = stream_get_contents($str);
        $error = stream_get_contents($errstr);
        if(!$error){
			$status = true;
		}else{
			$output = $error;
			$status = false;
		}
        return $output;
    }
    public function getHost():string{
        return $this->host;
    }
    public function getPort():int{
        return $this->port;
    }
    public function getUsername():string{
        return $this->username;
    }
    public function getPassword():string{
        return $this->password;
    }
}
