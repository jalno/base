<?php
namespace packages\base\Storage;

use packages\base\{IO\Node, IO\Directory, Router, Exception, Storage};

class LocalStorage extends Storage {

	/**
	 * @param array{"@class":class-string<LocalStorage>,"root":string,"type":"public"|"protected"|"private","@relative-to"?:string}
	 */
	public static function fromArray(array $data): self {
		if (!isset($data['root'])) {
			throw new Exception("'root' index is not present");
		}
		if (!isset($data['type'])) {
			throw new Exception("'type' index is not present");
		}
		if (!is_string($data['root'])) {
			throw new Exception("'root' value is not string");
		}
		if (!is_string($data['type'])) {
			throw new Exception("'type' value is not string");
		}
		if (isset($data['@relative-to']) and is_string($data['@relative-to'])) {
			$data['root'] = rtrim($data['@relative-to'], "/") . '/' . ltrim($data['root'], "/");
		}
		$data['root'] = new Directory\Local($data['root']);
		return new self($data['type'], $data['root']);
	}

	public function __construct(string $type, Directory $root) {
		parent::__construct($type, $root);
		if (!$this->root->exists()) {
			$this->root->make(true);
		}
	}

	public function getURL(Node $node, bool $absolute = false): string {
		if ($this->getType() != self::TYPE_PUBLIC) {
			throw new AccessForbiddenException($node);
		}

		$prefix = '';
		if ($absolute) {
			$prefix .= Router::getscheme() . '://' . Router::gethostname();
		}
		return $prefix . '/' . $node->getPath();
	}

	public function __serialize(): array {
		return array(
			"type" => $this->type,
			"root" => $this->root->getPath(),
		);
	}

	public function __unserialize(array $data): void {
		$this->type = $data["type"];
		$this->root = new Directory\Local($data["root"]);
	}
}
