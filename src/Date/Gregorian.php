<?php

namespace packages\base\Date;

class Gregorian implements DateInterface
{
    public static array $weekDays = [1, 2, 3, 4, 5, 6, 0];

    public static function format(string $format, ?int $timestamp = null): string
    {
        return date($format, $timestamp);
    }

    public static function strtotime(string $time, ?int $now = null): int
    {
        return strtotime($time, $now);
    }

    public static function getFirstDayOfWeek(): int
    {
        return self::$weekDays[0];
    }

    public static function getWeekDay(int $day): ?int
    {
        $key = array_search($day, self::$weekDays);

        return false !== $key ? $key : null;
    }

    public static function mktime(?int $hour = null, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null): int
    {
        if (null === $hour) {
            $hour = date('H');
        }

        return mktime($hour, $minute, $second, $month, $day, $year);
    }
}
