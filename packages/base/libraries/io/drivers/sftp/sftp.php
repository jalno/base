<?php
namespace packages\base\IO\drivers;
use \packages\base\ssh;
use \packages\base\IO\buffer;
class sftp{
	private $ssh;
	private $connection;
	function __construct(ssh $ssh){
		$this->ssh = $ssh;
		$this->connection = ssh2_sftp($this->ssh->connection());
	}
	public function getConnection(){
		return $this->connection;
	}
	public function getSSH():ssh{
		return $this->ssh;
	}
	public function upload($local, $remote, $mode = 0644){
    	if($fs = @fopen($local, 'rb')){
    		if($fd = @fopen("ssh2.sftp://".$this->connection.$remote, 'wb')){
    			$error = false;
    			while (!feof($fs)) {
					$content = fread($fs, 8192);
					if(fwrite($fd, $content) != strlen($content)){
						$error = true;
						break;
					}
				}
				fclose($fd);
    		}else{
				return false;
			}
    		fclose($fs);
    	}else{
			return false;
		}
    	return !$error;
	}
	public function download($remote, $local){
		if($fs = @fopen("ssh2.sftp://".$this->connection.$remote, 'rb')){
    		if($fd = @fopen($local, 'wb')){
    			$error = false;
    			while (!feof($fs)) {
					$content = fread($fs, 8192);
					if(fwrite($fd, $content) != strlen($content)){
						$error = true;
						break;
					}
				}
				fclose($fd);
    		}else{
				return false;
			}
    		fclose($fs);
    	}else{
			return false;
		}
    	return !$error;
	}
	public function put_contents($filename, $data, $flags=0){
		return file_put_contents("ssh2.sftp://".$this->connection.$filename, $data, $flags);
	}
	public function get_contents($filename){
		return file_get_contents("ssh2.sftp://".$this->connection.$filename);
	}
	public function is_file($filename){
		return $this->stat($filename) ? true : false;
	}
	public function is_dir($filename){
		return is_dir("ssh2.sftp://".$this->connection.$filename);
	}
	public function mkdir($pathname, $mode=0755){
		return ssh2_sftp_mkdir($this->connection,$pathname, $mode);
	}
	public function rmdir($pathname){
		return ssh2_sftp_rmdir($this->connection, $pathname);
	}
	public function unlink($filename){
		return ssh2_sftp_unlink($this->connection,$filename);
	}
	public function stat($filename){
		return @ssh2_sftp_stat($this->connection,$filename);
	}
	public function opendir($dir){
		return opendir("ssh2.sftp://".$this->connection.$dir);
	}
	public function listOfFiles($dir, bool $dirs = true, bool $subdirs = false){
		$items = array();
		$handle = $this->opendir($dir);
		while (false !== ($entry = readdir($handle))) {
			if($entry == '.' or $entry == '..'){
				continue;
			}
			$filename = $dir.'/'.$entry;
			$is_dir = $this->is_dir($filename);
			if($is_dir){
				if($dirs){
					$items[] = $filename;
				}
				if($subdirs){
					$items = array_merge($items, $this->listOfFiles($filename, $dirs, $subdirs));
				}
			}else{
				$items[] = $filename;
			}
		}
		return $items;
	}
	public function delete($path){
		if($this->is_dir($path)){
			$handle = $this->opendir($path);
			while (false !== ($entry = readdir($handle))) {
				if($entry == '.' or $entry == '..'){
					continue;
				}
				$this->delete($path.'/'.$entry);
				$this->rmdir($path.'/'.$entry);
			}
			$this->rmdir($path);
		}else{
			$this->unlink($path);
		}
	}
	public function open(string $filename, string $mode):buffer{
		return new buffer(@fopen("ssh2.sftp://".$this->connection.$filename, $mode));
	}
	public function size(string $filename):int{
		return @filesize("ssh2.sftp://".$this->connection.$filename);
	}
	public function rename(string $from, string $to):bool{
		return @ssh2_sftp_rename($this->connection, $from, $to);
	}
}