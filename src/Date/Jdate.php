<?php

namespace packages\base\Date;

use packages\base\Utility\Safe;

class Jdate implements DateInterface
{
    public static $weekDays = [6, 0, 1, 2, 3, 4, 5];

    public static function getFirstDayOfWeek(): int
    {
        return self::$weekDays[0];
    }

    public static function getWeekDay(int $day): ?int
    {
        $key = array_search($day, self::$weekDays);

        return false !== $key ? $key : null;
    }

    public static function format($type, $maket = 'now')
    {
        $transnumber = 0;
        $TZhours = 0;
        $TZminute = 0;
        $need = '';
        $result1 = '';
        $result = '';
        if ('now' == $maket) {
            $year = date('Y');
            $month = date('m');
            $day = date('d');
            list($jyear, $jmonth, $jday) = self::gregorian_to_jalali($year, $month, $day);
            $maket = mktime(date('H') + $TZhours, date('i') + $TZminute, date('s'), date('m'), date('d'), date('Y'));
        } else {
            $maket += $TZhours * 3600 + $TZminute * 60;
            $date = date('Y-m-d', $maket);
            list($year, $month, $day) = preg_split('/-/', $date);
            list($jyear, $jmonth, $jday) = self::gregorian_to_jalali($year, $month, $day);
        }
        $need = $maket;
        $year = date('Y', $need);
        $month = date('m', $need);
        $day = date('d', $need);
        $i = 0;
        $subtype = '';
        $subtypetemp = '';
        list($jyear, $jmonth, $jday) = self::gregorian_to_jalali($year, $month, $day);
        while ($i < strlen($type)) {
            $subtype = substr($type, $i, 1);
            if ('\\' == $subtypetemp) {
                $result .= $subtype;
                ++$i;
                continue;
            }
            switch ($subtype) {
                case 'A':
                    $result1 = date('a', $need);
                    if ('pm' == $result1) {
                        $result .= 'بعد ازظهر';
                    } else {
                        $result .= 'قبل ازظهر';
                    }
                    break;
                case 'a':
                    $result1 = date('a', $need);
                    if ('pm' == $result1) {
                        $result .= 'ب.ظ';
                    } else {
                        $result .= 'ق.ظ';
                    }
                    break;
                case 'd':
                    if ($jday < 10) {
                        $result1 = '0'.$jday;
                    } else {
                        $result1 = $jday;
                    }
                    if (1 == $transnumber) {
                        $result .= $result1;
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'D':
                    $result .= date('D', $need);
                    break;
                case 'F':
                    $result .= self::monthname($jmonth);
                    break;
                case 'g':
                    $result1 = date('g', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'G':
                    $result1 = date('G', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'h':
                    $result1 = date('h', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'H':
                    $result1 = date('H', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'i':
                    $result1 = date('i', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'j':
                    $result1 = $jday;
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }
                    break;
                case 'l':$result1 = date('l', $need);
                    if ('Saturday' == $result1) {
                        $result1 = 'شنبه';
                    } elseif ('Sunday' == $result1) {
                        $result1 = 'یکشنبه';
                    } elseif ('Monday' == $result1) {
                        $result1 = 'دوشنبه';
                    } elseif ('Tuesday' == $result1) {
                        $result1 = 'سه شنبه';
                    } elseif ('Wednesday' == $result1) {
                        $result1 = 'چهارشنبه';
                    } elseif ('Thursday' == $result1) {
                        $result1 = 'پنجشنبه';
                    } elseif ('Friday' == $result1) {
                        $result1 = 'جمعه';
                    }$result .= $result1;
                    break;
                case 'm':if ($jmonth < 10) {
                    $result1 = '0'.$jmonth;
                } else {
                    $result1 = $jmonth;
                }if (1 == $transnumber) {
                    $result .= self::Convertnumber2farsi($result1);
                } else {
                    $result .= $result1;
                }break;
                case 'M':$result .= self::short_monthname($jmonth);
                    break;
                case 'n':$result1 = $jmonth;
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }break;
                case 'N':$result = date('N', $need);
                    break;
                case 's':$result1 = date('s', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }break;
                case 'S':$result .= '&#1575;&#1605;';
                    break;
                case 't':$result .= self::lastday($month, $day, $year);
                    break;
                case 'w':$result1 = date('w', $need);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }break;
                case 'y':$result1 = substr($jyear, 2, 4);
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }break;
                case 'Y':$result1 = $jyear;
                    if (1 == $transnumber) {
                        $result .= self::Convertnumber2farsi($result1);
                    } else {
                        $result .= $result1;
                    }break;
                case 'U' :$result .= mktime(date('H', $need), date('i', $need), date('s', $need), date('m', $need), date('d', $need), date('Y', $need));
                    break;
                case 'Z' :$result .= self::days_of_year($jmonth, $jday, $jyear);
                    break;
                case 'L' :list($tmp_year, $tmp_month, $tmp_day) = self::jalali_to_gregorian(1384, 12, 1);
                    echo $tmp_day;
                    break;
                default:$result .= $subtype;
            }
            $subtypetemp = substr($type, $i, 1);
            ++$i;
        }

        return $result;
    }

    public static function gregorian_to_jalali($g_y, $g_m, $g_d)
    {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;
        $g_day_no = 365 * $gy + self::div($gy + 3, 4) - self::div($gy + 99, 100) + self::div($gy + 399, 400);
        for ($i = 0; $i < $gm; ++$i) {
            $g_day_no += $g_days_in_month[$i];
        }
        if ($gm > 1 && ((0 == $gy % 4 && 0 != $gy % 100) || (0 == $gy % 400))) {
            ++$g_day_no;
        }
        $g_day_no += $gd;
        $j_day_no = $g_day_no - 79;
        $j_np = self::div($j_day_no, 12053);
        $j_day_no = $j_day_no % 12053;
        $jy = 979 + 33 * $j_np + 4 * self::div($j_day_no, 1461);
        $j_day_no %= 1461;
        if ($j_day_no >= 366) {
            $jy += self::div($j_day_no - 1, 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }
        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
            $j_day_no -= $j_days_in_month[$i];
        }
        $jm = $i + 1;
        $jd = $j_day_no + 1;

        return [$jy, $jm, $jd];
    }

    public static function Convertnumber2farsi($srting)
    {
        $num0 = '0';
        $num1 = '1';
        $num2 = '2';
        $num3 = '3';
        $num4 = '4';
        $num5 = '5';
        $num6 = '6';
        $num7 = '7';
        $num8 = '8';
        $num9 = '9';
        $stringtemp = '';
        $len = strlen($srting);
        for ($sub = 0; $sub < $len; ++$sub) {
            if ('0' == substr($srting, $sub, 1)) {
                $stringtemp .= $num0;
            } elseif ('1' == substr($srting, $sub, 1)) {
                $stringtemp .= $num1;
            } elseif ('2' == substr($srting, $sub, 1)) {
                $stringtemp .= $num2;
            } elseif ('3' == substr($srting, $sub, 1)) {
                $stringtemp .= $num3;
            } elseif ('4' == substr($srting, $sub, 1)) {
                $stringtemp .= $num4;
            } elseif ('5' == substr($srting, $sub, 1)) {
                $stringtemp .= $num5;
            } elseif ('6' == substr($srting, $sub, 1)) {
                $stringtemp .= $num6;
            } elseif ('7' == substr($srting, $sub, 1)) {
                $stringtemp .= $num7;
            } elseif ('8' == substr($srting, $sub, 1)) {
                $stringtemp .= $num8;
            } elseif ('9' == substr($srting, $sub, 1)) {
                $stringtemp .= $num9;
            } else {
                $stringtemp .= substr($srting, $sub, 1);
            }
        }

        return $stringtemp;
    }

    public static function lastday($month, $day, $year)
    {
        $jday2 = '';
        $jdate2 = '';
        $lastdayen = date('d', mktime(0, 0, 0, $month + 1, 0, $year));
        list($jyear, $jmonth, $jday) = self::gregorian_to_jalali($year, $month, $day);
        $lastdatep = $jday;
        $jday = $jday2;
        while ('1' != $jday2) {
            if ($day < $lastdayen) {
                ++$day;
                list($jyear, $jmonth, $jday2) = self::gregorian_to_jalali($year, $month, $day);
                if ('1' == $jdate2) {
                    break;
                }
                if ('1' != $jdate2) {
                    ++$lastdatep;
                }
            } else {
                $day = 0;
                ++$month;
                if (13 == $month) {
                    $month = '1';
                    ++$year;
                }
            }
        }

        return $lastdatep - 1;
    }

    public static function days_of_year($jmonth, $jday, $jyear)
    {
        $year = '';
        $month = '';
        $result = '';
        if ('01' == $jmonth) {
            return $jday;
        }
        for ($i = 1; $i < $jmonth || 12 == $i; ++$i) {
            list($year, $month, $day) = self::jalali_to_gregorian($jyear, $i, '1');
            $result += self::lastday($month, $day, $year);
        }

        return $result + $jday;
    }

    public static function monthname($month)
    {
        switch ($month) {
            case '01':return 'فروردین';
                break;
            case '02':return 'اردیبهشت';
                break;
            case '03':return 'خرداد';
                break;
            case '04':return 'تیر';
                break;
            case '05':return 'مرداد';
                break;
            case '06':return 'شهریور';
                break;
            case '07':return 'مهر';
                break;
            case '08':return 'آبان';
                break;
            case '09':return 'آذر';
                break;
            case '10':return 'دی';
                break;
            case '11':return 'بهمن';
                break;
            case '12':return 'اسفند';
                break;
        }
    }

    public static function mstart($month, $day, $year)
    {
        list($jyear, $jmonth, $jday) = self::gregorian_to_jalali($year, $month, $day);
        list($year, $month, $day) = self::jalali_to_gregorian($jyear, $jmonth, '1');
        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        return date('w', $timestamp);
    }

    public static function short_monthname($month)
    {
        switch ($month) {
            case '01':return 'فرو';
                break;
            case '02':return 'ارد';
                break;
            case '03':return 'خرد';
                break;
            case '04':return 'تیر';
                break;
            case '05':return 'مرد';
                break;
            case '06':return 'شهر';
                break;
            case '07':return 'مهر';
                break;
            case '08':return 'آبا';
                break;
            case '09':return 'آذر';
                break;
            case '10':return 'دی';
                break;
            case '11':return 'بهم';
                break;
            case '12':return 'اسف';
                break;
        }
    }

    public static function mktime($hour = null, $minute = null, $second = null, $month = null, $day = null, $year = null)
    {
        list($year, $month, $day) = self::jalali_to_gregorian($year, $month, $day);

        return mktime($hour, $minute, $second, $month, $day, $year);
    }

    public static function strtotime($str, $now = null)
    {
        if ($date = Safe::is_date($str)) {
            if (!isset($date['h'])) {
                $date['h'] = 0;
            }
            if (!isset($date['i'])) {
                $date['i'] = 0;
            }
            if (!isset($date['s'])) {
                $date['s'] = 0;
            }

            return self::mktime($date['h'], $date['i'], $date['s'], $date['m'], $date['d'], $date['Y']);
        } else {
            return false;
        }
    }

    public static function is_kabise($year)
    {
        if (0 == $year % 4 && 0 != $year % 100) {
            return true;
        } else {
            return false;
        }
    }

    public static function jcheckdate($month, $day, $year)
    {
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        if ($month <= 12 && $month > 0) {
            if ($j_days_in_month[$month - 1] >= $day && $day > 0) {
                return 1;
            }
            if (self::is_kabise($year)) {
                echo 'Asdsd';
            }
            if (self::is_kabise($year) && 31 == $j_days_in_month[$month - 1]) {
                return 1;
            }
        }

        return 0;
    }

    public static function div($a, $b)
    {
        return (int) ($a / $b);
    }

    public static function jalali_to_gregorian($j_y, $j_m, $j_d)
    {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;
        $j_day_no = 365 * $jy + self::div($jy, 33) * 8 + self::div($jy % 33 + 3, 4);
        for ($i = 0; $i < $jm; ++$i) {
            $j_day_no += $j_days_in_month[$i];
        }
        $j_day_no += $jd;
        $g_day_no = $j_day_no + 79;
        $gy = 1600 + 400 * self::div($g_day_no, 146097);
        $g_day_no = $g_day_no % 146097;
        $leap = true;
        if ($g_day_no >= 36525) {
            --$g_day_no;
            $gy += 100 * self::div($g_day_no, 36524);
            $g_day_no = $g_day_no % 36524;
            if ($g_day_no >= 365) {
                ++$g_day_no;
            } else {
                $leap = false;
            }
        }
        $gy += 4 * self::div($g_day_no, 1461);
        $g_day_no %= 1461;
        if ($g_day_no >= 366) {
            $leap = false;
            --$g_day_no;
            $gy += self::div($g_day_no, 365);
            $g_day_no = $g_day_no % 365;
        }
        for ($i = 0; $g_day_no >= $g_days_in_month[$i] + (1 == $i && $leap); ++$i) {
            $g_day_no -= $g_days_in_month[$i] + (1 == $i && $leap);
        } $gm = $i + 1;
        $gd = $g_day_no + 1;

        return [$gy, $gm, $gd];
    }
}
