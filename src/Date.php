<?php

namespace packages\base;

use InvalidArgumentException;
use packages\base\Date\DateInterface;
use packages\base\Date\Gregorian;
use packages\base\Date\Hdate;
use packages\base\Date\Jdate;

class Date implements DateInterface
{
    public static array $presetsFormats = [
        'Q' => 'm/d/Y',
        'q' => 'n/j/Y',
        'QQ' => 'F d Y',
        'qq' => 'M d Y',
        'QQQ' => 'F d Y g:i A',
        'qqq' => 'M d Y g:i A',
        'QQQQ' => 'l, F d Y g:i A',
        'qqqq' => 'D, M d Y g:i A',
        'QT' => 'g:i A',
        'QTS' => 'g:i:s A',
    ];

    public static function setPresetsFormat(string $key, string $format): void
    {
        if (!isset(self::$presetsFormats[$key])) {
            throw new Exception("'{$key}' is not a presets formats. allowed presets formats is: ".Json\encode(self::$presetsFormats));
        }
        self::$presetsFormats[$key] = $format;
    }

    protected static ?string $calendar = null;

    public static function setCanlenderName(string $name): void
    {
        self::$calendar = $name;
    }

    public static function getCanlenderName(): string
    {
        return self::$calendar;
    }

    public static function setTimeZone(string $timezone): void
    {
        if (!date_default_timezone_set($timezone)) {
            throw new Exception("timezone identifier isn't valid");
        }
    }

    public static function getTimeZone(): string
    {
        return date_default_timezone_get();
    }

    public static function format(string $format, ?int $timestamp = null): string
    {
        static::init();
        $presetsFormats = array_reverse(self::$presetsFormats);
        $format = str_replace(array_keys($presetsFormats), array_values($presetsFormats), $format);

        return call_user_func([static::getCalendarFQCN(), 'format'], $format, $timestamp);
    }

    public static function strtotime(string $time, ?int $now = null): int
    {
        static::init();

        return call_user_func([static::getCalendarFQCN(), 'strtotime'], $time, $now);
    }

    public static function getFirstDayOfWeek(): int
    {
        static::init();

        return call_user_func([static::getCalendarFQCN(), 'getFirstDayOfWeek']);
    }

    public static function getWeekDay(int $day): ?int
    {
        static::init();

        return call_user_func([static::getCalendarFQCN(), 'getWeekDay'], $day);
    }

    public static function mktime(?int $hour = null, ?int $minute = null, ?int $second = null, ?int $month = null, ?int $day = null, ?int $year = null): int
    {
        static::init();
        return call_user_func([static::getCalendarFQCN(), 'mktime'], $hour, $minute, $second, $month, $day, $year);
    }

    public static function time(): int
    {
        return time();
    }

    public static function setDefaultCalendar(): void
    {
        static::setCanlenderName(Options::get('packages.base.date.calendar') ?: 'gregorian');
    }

    public static function relativeTime(int $time, string $format = 'short'): string
    {
        $now = static::time();
        $mine = $time - $now;
        if (0 == $mine) {
            return t('date.relatively.now');
        }
        $steps = ['y', 'm', 'w', 'd', 'h', 'i', 's'];
        if ('short' == $format) {
            $format = 'y';
        } elseif ('long' == $format) {
            $format = 's';
        } elseif (!in_array($format, $steps)) {
            throw new InvalidArgumentException('wrong format, allowed: y, m, w, d, h, i, s');
        }
        $maxStep = array_search($format, $steps);
        $abs = abs($mine);
        $expr = [];
        foreach ([['years', 31536000], ['months', 2592000], ['weeks', 604800], ['days', 86400], ['hours', 3600], ['minutes', 60]] as $key => $item) {
            $matched = false;
            if ($abs >= $item[1]) {
                $matched = true;
            }
            $isLast = (count($expr) + $matched and $key >= $maxStep);
            if ($matched) {
                $number = $isLast ? ceil($abs / $item[1]) : floor($abs / $item[1]);
                $expr[] = t('date.relatively.'.$item[0], ['number' => $number]);
                $abs = $abs % $item[1];
            }
            if ($isLast) {
                break;
            }
        }
        if ($abs and (!$expr or 6 == $maxStep)) {
            $expr[] = t('date.relatively.seconds', ['number' => $abs]);
        }
        $expr_text = $expr[0];
        $count = count($expr);
        for ($x = 1; $x < $count; ++$x) {
            $expr_text = t('date.relatively.and', [
                'expr1' => $expr_text,
                'expr2' => $expr[$x],
            ]);
        }
        if ($mine < 0) {
            return t('date.relatively.ago', ['expr' => $expr_text]);
        } else {
            return t('date.relatively.later', ['expr' => $expr_text]);
        }
    }

    public static function init(): void
    {
        if (!self::$calendar) {
            static::setDefaultCalendar();
        }
    }

    /**
     * @return class-string<DateInterface>
     */
    protected static function getCalendarFQCN(): string {
        return  match(strtolower(self::$calendar)) {
            "jdate" => Jdate::class,
            "gregorian" => Gregorian::class,
            "hdate" => Hdate::class,
            default => __NAMESPACE__ . '\\Date\\' . self::$calendar,
        };
    }
}
