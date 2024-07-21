<?php

namespace packages\base\IO;

function mkdir($pathname, $recursive = false, $mode = 0755)
{
    if ($recursive) {
        $dirs = explode('/', $pathname);
        $dir = '';
        $result = true;
        foreach ($dirs as $part) {
            $dir .= $part.'/';
            if (!is_dir($dir) and $dir) {
                if (!\mkdir($dir, $mode)) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    } else {
        return \mkdir($pathname, $mode, $recursive);
    }
}
function is_dir($dir)
{
    return \is_dir($dir);
}
function removeLastSlash($path)
{
    while ('/' == substr($path, -1)) {
        $path = substr($path, 0, strlen($path) - 1);
    }

    return $path;
}
