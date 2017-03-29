<?php
namespace packages\base\IO\drivers;
use \packages\base\ssh;
class sftp{
	private $connection;
	function __construct(ssh $ssh){
		$this->connection = ssh2_sftp($ssh->connection());
	}
	public function upload($local,$remote,$mode = 0644){
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
	public function unlink($filename){
		return ssh2_sftp_unlink($this->connection,$filename);
	}
	public function stat($filename){
		return @ssh2_sftp_stat($this->connection,$filename);
	}
}
