<?php

namespace packages\base\Date;

interface date_interface
{
    public static function format($format, $timestamp = null);

    public static function strtotime($time, $now = null);

    public static function getFirstDayOfWeek(): int;

    public static function getWeekDay(int $day): ?int;

    public static function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null);
}
