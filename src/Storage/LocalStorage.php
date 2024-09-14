<?php

namespace packages\base\Storage;

use packages\base\Exception;
use packages\base\IO\Directory;
use packages\base\IO\Node;
use packages\base\Storage;

class LocalStorage extends Storage
{
    /**
     * @param array{"@class":class-string<LocalStorage>,"root":string,"type":"public"|"protected"|"private","@relative-to"?:string,"url"?:string}
     */
    public static function fromArray(array $data): self
    {
        foreach(['root', 'type'] as $key) {
            if (!isset($data[$key])) {
                throw new Exception("'{$key}' index is not present");
            }
            if (!is_string($data[$key])) {
                throw new Exception("'{$key}' value is not string");
            }
        }
        if (isset($data['@relative-to']) and is_string($data['@relative-to'])) {
            $data['root'] = ltrim($data['root'], '/');
            if (str_starts_with($data['root'], "storage/")) {
                $data['root'] = substr($data['root'], 8);
            }
            $data['root'] = rtrim($data['@relative-to'], '/').'/'.ltrim($data['root'], '/');
        }
        if ($data['type'] == self::TYPE_PUBLIC) {
            if (!isset($data['url'])) {
                throw new Exception("'url' index is not present");
            }
            if (!is_string($data['url'])) {
                throw new Exception("'url' value is not string");
            }
        }
        $data['root'] = new Directory\Local($data['root']);

        $instance = new self($data['type'], $data['root']);
        if (isset($data['url'])) {
            $instance->url = $data['url'];
        }

        return $instance;
    }

    protected ?string $url = null;

    public function __construct(string $type, Directory $root)
    {
        parent::__construct($type, $root);
        if (!$this->root->exists()) {
            $this->root->make(true);
        }
    }

    public function getURL(Node $node): string
    {
        if (self::TYPE_PUBLIC != $this->getType()) {
            throw new AccessForbiddenException($node);
        }

        return $this->url. "/" . $node->getRelativePath($this->root);
    }

    public function setUrlPrefix(?string $url): static {
        if ($this->type == self::TYPE_PUBLIC and !$url) {
            throw new Exception("You cannot remove url prefix of a public storage");
        }
        $this->url = rtrim($url, "/");;

        return $this;
    }

    public function __serialize(): array
    {
        return [
            'type' => $this->type,
            'root' => $this->root->getPath(),
            'url' => $this->url,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->type = $data['type'];
        $this->root = new Directory\Local($data['root']);
        $this->url = $data['url'];
    }
}
