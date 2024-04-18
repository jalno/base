<?php

namespace packages\base\Date;

class Gregorian implements date_interface
{
    public static $weekDays = [1, 2, 3, 4, 5, 6, 0];

    public static function format($format, $timestamp = null)
    {
        if (null === $timestamp) {
            $timestamp = time();
        }

        return date($format, $timestamp);
    }

    public static function strtotime($time, $now = null)
    {
        if (null === $now) {
            $now = time();
        }

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

    public static function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        if (null === $hour) {
            $hour = date('H');
        }
        if (null === $minute) {
            $minute = date('i');
        }
        if (null === $second) {
            $second = date('s');
        }
        if (null === $month) {
            $month = date('n');
        }
        if (null === $day) {
            $day = date('j');
        }
        if (null === $year) {
            $year = date('Y');
        }

        return mktime($hour, $minute, $second, $month, $day, $year);
    }
}
