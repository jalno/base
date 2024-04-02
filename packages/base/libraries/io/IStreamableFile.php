<?php

namespace packages\base\IO;

interface IStreamableFile
{
    public function open(string $mode): Buffer;
}
