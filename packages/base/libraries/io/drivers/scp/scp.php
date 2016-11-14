<?php
namespace packages\base\IO\drivers;
use \packages\base\ssh;
class scp{
	private $ssh;
	function __construct(ssh $ssh){
		$this->ssh = $ssh;
	}
	public function upload($local,$remote,$mode = 0644){
		return ssh2_scp_send($this->ssh->connection(), $local,$remote, $mode);
	}
	public function download($remote, $local){
		return ssh2_scp_recv($this->ssh->connection(), $remote, $local);
	}
}
