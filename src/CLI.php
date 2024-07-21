<?php

namespace packages\base;

class CLI
{
    public static $request = [];
    public static $process = [];

    public static function set()
    {
        self::$request['parameters'] = self::getParameters($_SERVER['argv']);
        self::$process['pid'] = getmypid();
    }

    public static function getParameter($name)
    {
        if (isset(self::$request['parameters'][$name])) {
            return self::$request['parameters'][$name];
        }

        return null;
    }

    /**
     * to manage arguments on cli use.
     *
     * @return array
     */
    public static function getParameters($params)
    {
        $params = $params ?? [];
        $return = [];
        for ($x = 0; $x != count($params); ++$x) {
            if (0 == $x) {
                continue;
            }
            if ('--' == substr($params[$x], 0, 2)) {
                $temp = explode('=', $params[$x], 2);
                $temp[0] = substr($temp[0], 2);
                if (isset($return[$temp[0]])) {
                    if (!is_array($return[$temp[0]])) {
                        $return[$temp[0]] = [$return[$temp[0]]];
                    }
                    $return[$temp[0]][] = isset($temp[1]) ? trim($temp[1]) : true;
                } else {
                    $return[$temp[0]] = isset($temp[1]) ? trim($temp[1]) : true;
                }
            } elseif ('-' == substr($params[$x], 0, 1)) {
                $temp[0] = substr($params[$x], 1, 1);
                $temp[1] = trim(substr($params[$x], 2));
                if (!$temp[1]) {
                    $temp[1] = true;
                }
                $return[$temp[0]] = $temp[1];
                if (isset($return[$temp[0]])) {
                    if (!is_array($return[$temp[0]])) {
                        $return[$temp[0]] = [$return[$temp[0]]];
                    }
                    $return[$temp[0]][] = $temp[1];
                } else {
                    $return[$temp[0]] = $temp[1];
                }
            }
        }

        return $return;
    }

    public static function readLine(string $message = ''): string
    {
        if ($message) {
            echo $message;
        }
        $line = fgets(STDIN);
        if ("\n" == substr($line, -1)) {
            $line = substr($line, 0, strlen($line) - 1);
        }

        return $line;
    }
}
