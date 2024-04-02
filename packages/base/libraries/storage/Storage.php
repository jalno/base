<?php

namespace packages\base;

use packages\base\IO\Directory;
use packages\base\IO\File;
use packages\base\IO\Node;

abstract class Storage implements \JsonSerializable
{
    public const TYPE_PUBLIC = 'public';
    public const TYPE_PROTECTED = 'protected';
    public const TYPE_PRIVATE = 'private';

    /**
     * @param array{"@class":class-string<Storage>}
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['@class'])) {
            throw new Exception("'@class' index is not present");
        }
        if (!is_string($data['@class'])) {
            throw new Exception("'{$data['@class']}' value is not string");
        }
        $data['@class'] = str_replace('/', '\\', $data['@class']);
        switch ($data['@class']) {
            case 'local': $data['@class'] = Storage\LocalStorage::class;
                break;
        }
        if (!class_exists($data['@class'])) {
            throw new Exception("'{$data['@class']}' is undefined storage type");
        }
        if (!is_a($data['@class'], self::class, true)) {
            throw new Exception("'{$data['@class']}' is not subclass of '".self::class."'");
        }
        $ref = new \ReflectionMethod($data['@class'], __FUNCTION__);
        if (self::class === $ref->getDeclaringClass()->getName()) {
            throw new Exception("'{$data['@class']}' not implementing '".__FUNCTION__."' method");
        }

        return call_user_func([$data['@class'], __FUNCTION__], $data);
    }

    /**
     * @var "public"|"protected"|"private"
     */
    protected string $type;
    protected Directory $root;

    /**
     * @param "public"|"protected"|"private" $type
     */
    public function __construct(string $type, Directory $root)
    {
        $this->setType($type);
        $this->setRoot($root);
    }

    public function setRoot(Directory $root): void
    {
        $this->root = $root;
    }

    public function getRoot(): Directory
    {
        return $this->root;
    }

    public function file(string $path): File
    {
        return $this->root->file($path);
    }

    public function directory(string $path): Directory
    {
        return $this->root->directory($path);
    }

    public function setType(string $type): void
    {
        $type = strtolower($type);
        if (!in_array($type, [self::TYPE_PUBLIC, self::TYPE_PROTECTED, self::TYPE_PRIVATE])) {
            throw new \InvalidArgumentException("The given argument type is not valid! gived: [{$type}]");
        }
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    abstract public function getURL(Node $node): string;

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed
     */
    public function jsonSerialize(): array
    {
        return [
            '@class' => get_class($this),
            'type' => $this->type,
            'root' => $this->root,
        ];
    }
}
