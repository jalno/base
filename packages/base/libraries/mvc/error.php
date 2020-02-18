<?php
namespace packages\base\view;

class Error extends \Exception implements \Serializable {
	const SUCCESS = 'success';
	const WARNING = 'warning';
	const FATAL = 'fatal';
	const NOTICE = 'notice';
	const FULL = 'full';
	const SUMMARY= 'summary';
	protected $data;
	protected $trace;
	protected $type = self::FATAL;
	protected $serializing_type = self::FULL;
	public function __construct(string $message, string $code) {
		$this->message = $message;
		$this->code = $code;
	}
	public function setMessage(string $message): void {
		$this->message = $message;
	}
	public function setCode(string $code): void {
		$this->code = $code;
	}
	public function setData($val, $key = null): void {
		if ($key) {
			$this->data[$key] = $val;
		} else {
			$this->data = $val;
		}
	}
	public function getData($key = null) {
		if ($key) {
			return(isset($this->data[$key]) ? $this->data[$key] : null);
		} else {
			return $this->data;
		}
	}
	public function setType(string $type): void {
		if (!in_array($type, array(self::SUCCESS, self::WARNING,self::FATAL,self::NOTICE))) {
			throw new \Exception("type");
		}
		$this->type = $type;
	}
	public function getType(): string {
		return $this->type;
	}
	public function setSerializeType(string $code): void {
		if (!in_array($code, array(self::FULL, self::SUMMARY))) {
			throw new \Exception("serializing_type");
		}
		$this->serializing_type = $code;
	}
	public function getSerializeType(): string {
		return $this->serializing_type;
	}
	public function serialize(): string {
        return serialize(array(
			"type" => $this->type,
			"serializing_type" => $this->serializing_type,
			"code" => $this->code,
			"message" => $this->message,
			"file" => $this->file,
			"line" => $this->line,
			"trace" => $this->serializing_type == self::FULL ? $this->getTrace() : $this->getTraceAsString(),
		));
	}
	public function unserialize($serialized): void {
		$data = unserialize($serialized);
		$this->type = $data["type"];
		$this->serializing_type = $data["serializing_type"];
		$this->code = $data["code"];
		$this->message = $data["message"];
		$this->file = $data["file"];
		$this->line = $data["line"];
		$this->trace = $data["trace"];
	}
}
