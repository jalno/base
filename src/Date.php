<?php

namespace packages\base;

use packages\base\Date\CalendarNotExist;
use packages\base\Date\DateInterface;

class Date implements DateInterface
{
    public static $presetsFormats = [
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

    public static function setPresetsFormat(string $key, string $format)
    {
        if (!isset(self::$presetsFormats[$key])) {
            throw new Exception("'{$key}' is not a presets formats. allowed presets formats is: ".Json\encode(self::$presetsFormats));
        }
        self::$presetsFormats[$key] = $format;
    }
    protected static $calendar;
    protected static $inited = false;

    public static function setCanlenderName($name)
    {
        $log = Log::getInstance();
        $classname = __NAMESPACE__.'\\date\\'.$name;
        $log->debug('looking for', $classname, 'calendar');
        if (class_exists($classname)) {
            $log->reply('found');
            self::$calendar = $name;
        } else {
            $log->reply()->fatal('Notfound');
            throw new CalendarNotExist($name);
        }
    }

    public static function getCanlenderName()
    {
        return self::$calendar;
    }

    public static function setTimeZone(string $timezone)
    {
        $log = Log::getInstance();
        $log->debug('check given timezone ('.$timezone.') is valid?');
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL))) {
            $log->reply()->fatal('is not valid');
            throw new Date\TimeZoneNotValid($timezone);
        } else {
            $log->reply('is valid');
        }
        date_default_timezone_set($timezone);
    }

    public static function getTimeZone(): string
    {
        self::init();

        return date_default_timezone_get();
    }

    public static function format($format, $timestamp = null)
    {
        self::init();
        if (null === $timestamp) {
            $timestamp = self::time();
        }
        $presetsFormats = array_reverse(self::$presetsFormats);
        $format = str_replace(array_keys($presetsFormats), array_values($presetsFormats), $format);

        return call_user_func_array([__NAMESPACE__.'\\date\\'.self::$calendar, 'format'], [$format, $timestamp]);
    }

    public static function strtotime($time, $now = null)
    {
        self::init();
        if (null === $now) {
            $now = self::time();
        }

        return call_user_func_array([__NAMESPACE__.'\\date\\'.self::$calendar, 'strtotime'], [$time, $now]);
    }

    public static function getFirstDayOfWeek(): int
    {
        self::init();

        return call_user_func([__NAMESPACE__.'\\date\\'.self::$calendar, 'getFirstDayOfWeek']);
    }

    public static function getWeekDay(int $day): ?int
    {
        self::init();

        return call_user_func([__NAMESPACE__.'\\date\\'.self::$calendar, 'getWeekDay'], $day);
    }

    public static function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        self::init();
        $now = explode('/', self::format('Y/m/d/H/i/s'));
        if (null === $year) {
            $year = $now[0];
        }
        if (null === $day) {
            $day = $now[2];
        }
        if (null === $month) {
            $month = $now[1];
        }
        if (null === $hour) {
            $hour = $now[3];
        }
        if (null === $minute) {
            $minute = $now[4];
        }
        if (null === $second) {
            $second = $now[5];
        }
        if (self::$calendar) {
            return call_user_func_array([__NAMESPACE__.'\\date\\'.self::$calendar, 'mktime'], [$hour, $minute, $second, $month, $day, $year]);
        }
    }

    /**
     * @return int
     */
    public static function time()
    {
        return time();
    }

    public static function setDefaultcalendar()
    {
        $log = Log::getInstance();
        $defaultOption = [
            'calendar' => 'gregorian',
        ];
        $log->debug('looking for packages.base.date option');
        if (($option = Options::load('packages.base.date')) !== false) {
            $log->reply($option);
            $defaultOption = array_replace_recursive($defaultOption, $option);
            $log->debug('set calendar to', $defaultOption['calendar']);
            self::setCanlenderName($defaultOption['calendar']);
        } else {
            $log->reply('Not defined');
        }
        $log->debug('set calendar to', $defaultOption['calendar']);
        self::setCanlenderName($defaultOption['calendar']);
    }

    public static function setDefaultTimeZone()
    {
        $log = Log::getInstance();
        $defaultOption = [];
        $log->debug('looking for packages.base.date option');
        $option = Options::get('packages.base.date');
        if (false !== $option) {
            $log->reply('found');
        }
        if (!isset($defaultOption['timezone'])) {
            return;
        }
        $log->debug('set timezone to', $defaultOption['timezone']);
        self::setTimeZone($defaultOption['timezone']);
    }

    public static function relativeTime(int $time, string $format = 'short'): string
    {
        $now = self::time();
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
            throw new \TypeError('wrong format, allowed: y, m, w, d, h, i, s');
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

    public static function init()
    {
        if (self::$inited) {
            return;
        }
        self::setDefaultTimeZone();
        if (!self::$calendar) {
            self::setDefaultcalendar();
        }
        if (!self::$calendar) {
            throw new Date\NoCalendarException();
        }
        self::$inited = true;
    }
}
