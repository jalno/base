<?php
namespace packages\base\view;

use packages\base\Exception;

class Error extends Exception implements \Serializable {
	const SUCCESS = 'success';
	const WARNING = 'warning';
	const FATAL = 'fatal';
	const NOTICE = 'notice';
	protected $type = self::FATAL;
	protected $short_trace = false;
	protected $code;
	protected $message;
	protected $data;
	protected $trace;
	public function __construct(?string $code = null) {
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
			throw new Exception("type");
		}
		$this->type = $type;
	}
	public function getType(): string {
		return $this->type;
	}
	public function saveShortTrace(bool $saveShortTrace = true): void {
		$this->short_trace = $saveShortTrace;
	}
	public function isShortTrace(): bool {
		return $this->short_trace;
	}
	public function serialize(): string {
        return serialize(array(
			"type" => $this->type,
			"short_trace" => $this->short_trace,
			"code" => $this->code,
			"message" => $this->message,
			"file" => $this->file,
			"line" => $this->line,
			"trace" => $this->short_trace ? $this->getTraceAsString() : $this->getTrace(),
		));
	}
	public function unserialize($serialized): void {
		$data = unserialize($serialized);
		$this->type = $data["type"];
		$this->short_trace = $data["short_trace"];
		$this->code = $data["code"];
		$this->message = $data["message"];
		$this->file = $data["file"];
		$this->line = $data["line"];
		$this->trace = $data["trace"];
	}
}
