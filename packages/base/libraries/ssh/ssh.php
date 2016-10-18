<?php
namespace packages\base;
use \packages\base\ssh\ConnectionException;
class ssh{
    private $connection = null;
    private $sftp = null;
    public $connectd = false;
    function __construct($host, $port){
        if(($this->connection = @ssh2_connect($host, $port)) === false){
            throw new ConnectionException;
        }
    }
	function AuthByPassword($username,$password){
		return @ssh2_auth_password($this->connection, $username, $password);
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
}
