<?php

namespace packages\base\storage;

use packages\base\Exception;
use packages\base\IO\Node;

class AccessForbiddenException extends Exception
{
    protected Node $node;

    public function __construct(Node $node, string $message = '')
    {
        $this->node = $node;
        $this->message = $message;
    }

    public function getNode(): Node
    {
        return $this->node;
    }
}
