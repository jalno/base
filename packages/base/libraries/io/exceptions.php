<?php
namespace packages\base\IO;
class Exception extends \Exception{
	protected $targetFile;
	public function __construct($file, $message = ''){
		$this->targetFile = $file;
		$this->message = $message;
	}
	public function getTargetFile():file{
		return $this->targetFile;
	}
}
class NotFoundException extends Exception{
	public function __construct($file){
		parent::__construct($file, "cannot find the IO resource");
	}
}
class ReadException extends Exception{
	public function __construct(file $file){
		parent::__construct($file, "cannot read the file");
	}
}
class WriteException extends Exception{
	private $source;
	public function __construct(file $source, file $dist){
		parent::__construct($dist, "cannot write the file");
		$this->source = $source;
	}
	public function getSource():file{
		return $this->source;
	}
}
class SocketException extends \Exception{
	
}
class CreateSocketException extends SocketException{
	
}
class BindSocketException extends SocketException{

}
class ListenSocketException extends SocketException{

}
class RawListenSocketException extends SocketException{
	public function __construct(){
		parent::__construct("cannot listen on RAW Sockets");
	}
}