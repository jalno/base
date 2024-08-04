<?php

namespace packages\base\Date;

interface DateInterface
{
    public static function format(string $format, ?int $timestamp = null): string;

    public static function strtotime(string $time, ?int $now = null): int;

    public static function getFirstDayOfWeek(): int;

    public static function getWeekDay(int $day): ?int;

    public static function mktime(?int $hour = null, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null): int;
}
