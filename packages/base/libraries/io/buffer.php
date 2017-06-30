<?php
namespace packages\base\IO;
class buffer{
    private $buffer;
    public function __construct($buffer){
        $this->buffer = $buffer;
    }
    public function __destruct(){
        if($this->buffer){
            $this->close();
        }
    }
    public function close(){
        fclose($this->buffer);
        $this->buffer = null;
    }
    public function read(int $length): string{
        return fread($this->buffer, $length);
    }
    public function readLine(int $length = 0){
        if($length == 0){
            return fgets($this->buffer);
        }
        return fgets($this->buffer, $length);
    }
    public function write(string $data): int{
        return fwrite($this->buffer, $data);
    }
}