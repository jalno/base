<?php

namespace packages\base\Utility;

class Safe
{
    /**
     * Safe compare two float value to avoid the floating-point problem.
     *
     * @return int positive value if a > b, zero if a = b and a negative value if a < b
     *
     * @see https://www.php.net/manual/en/language.types.float.php#language.types.float.comparison
     */
    public static function floats_cmp(float $a, float $b): int
    {
        if (0 == $a or 0 == $b) {
            return $a <=> $b;
        } elseif (abs(($a - $b) / $b) < PHP_FLOAT_EPSILON) { // PHP_FLOAT_EPSILON available as of PHP 7.2.0.
            return 0;
        } elseif ($a - $b > 0) {
            return 1;
        } else {
            return -1;
        }
    }

    public static function string($str)
    {
        $str = trim($str);
        $str = str_replace(['\\', '\'', '"'], '', $str);
        $str = htmlentities($str, ENT_IGNORE | ENT_SUBSTITUTE | ENT_DISALLOWED, 'UTF-8');

        return $str;
    }

    public static function number($num, $negative = false)
    {
        if (preg_match($negative ? "/(-?\d+)/" : "/(\d+)/", $num, $matches)) {
            return (int) $matches[1];
        }
    }

    public static function date($str)
    {
        $str = trim($str);

        return preg_match('/(\d{4})\/(\d{2})\/(\d{2})/', $str, $matches) ? ['year' => $matches[1], 'month' => $matches[2], 'day' => $matches[3]] : '';
    }

    public static function is_date($str)
    {
        $str = trim($str);
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})((\s+)(\d{1,2}))?(:(\d{1,2}))?(:(\d{1,2}))?$/', $str, $matches)) {
            $d = [
                'Y' => $matches[1],
                'm' => $matches[2],
                'd' => $matches[3],
            ];
            if (isset($matches[6]) and $matches[6] >= 0 and $matches[6] < 24) {
                $d['h'] = $matches[6];
            }
            if (isset($matches[8]) and $matches[8] >= 0 and $matches[8] < 60) {
                $d['i'] = $matches[8];
            }
            if (isset($matches[10]) and $matches[10] >= 0 and $matches[8] < 60) {
                $d['s'] = $matches[10];
            }

            return $d;
        } else {
            return false;
        }
    }

    public static function is_email($address)
    {
        return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
    }

    public static function is_cellphone_ir(string $cellphone): bool
    {
        $length = strlen($cellphone);
        if ((10 == $length and '9' == substr($cellphone, 0, 1)) // 9131101234
            or (11 == $length and '09' == substr($cellphone, 0, 2)) // 09131101234
            or (12 == $length and '989' == substr($cellphone, 0, 3)) // 989131101234
            or (13 == $length and '9809' == substr($cellphone, 0, 4)) // 9809131101234
            or (13 == $length and '+989' == substr($cellphone, 0, 4)) // +989131101234
            or (14 == $length and '98989' == substr($cellphone, 0, 5))) { // 98989131101234
            $sub4 = '';
            switch ($length) {
                case 10: // 913
                    $sub4 = '0'.substr($cellphone, 0, 3);
                    break;
                case 11: // 0913
                    $sub4 = substr($cellphone, 0, 4);
                    break;
                case 12: // 98913
                    $sub4 = '0'.substr($cellphone, 2, 3);
                    break;
                case 13: // 9809 || +98913
                    if ('9809' == substr($cellphone, 0, 4)) {
                        $sub4 = substr($cellphone, 2, 4);
                    } elseif ('+989' == substr($cellphone, 0, 4)) {
                        $sub4 = '0'.substr($cellphone, 3, 3);
                    }
                    break;
                case 14: // 9898913
                    $sub4 = '0'.substr($cellphone, 4, 3);
                    break;
            }
            switch ($sub4) {
                case '0910':case '0911':case '0912':case '0913':case '0914':case '0915':case '0916':case '0917':case '0918':case '0919':case '0990':case '0991':case '0992':case '0993':case '0994':case '0996': // TCI
                case '0930':case '0933':case '0935':case '0936':case '0937':case '0938':case '0939': // IranCell
                case '0900':case '0901':case '0902':case '0903':case '0904':case '0905':case '0941': // IranCell - ISim
                case '0920':case '0921':case '0922':case '0923': // RighTel
                case '0931': // Spadan
                case '0932': // Taliya
                case '0934': // TKC
                case '0998': // ShuttleMobile
                case '0999': // Private Sector: ApTel, Azartel, LOTUSTEL, SamanTel
                    return true;
                default:
                    return false;
            }
        }

        return false;
    }

    public static function cellphone_ir(string $cellphone)
    {
        $length = strlen($cellphone);
        if ((10 == $length and '9' == substr($cellphone, 0, 1)) // 9131101234
            or (11 == $length and '09' == substr($cellphone, 0, 2)) // 09131101234
            or (12 == $length and '989' == substr($cellphone, 0, 3)) // 989131101234
            or (13 == $length and '9809' == substr($cellphone, 0, 4)) // 9809131101234
            or (13 == $length and '+989' == substr($cellphone, 0, 4)) // +989131101234
            or (14 == $length and '98989' == substr($cellphone, 0, 5))) { // 98989131101234
            return substr($cellphone, $length - 10);
        }

        return false;
    }

    public static function bool($value)
    {
        return 'true' == $value or 1 == $value;
    }

    public static function is_ip4($ip)
    {
        $parts = explode('.', $ip);
        if (4 != count($parts)) {
            return false;
        }
        foreach ($parts as $key => $part) {
            if (0 == $key) {
                if ($part <= 0 or $part > 255) {
                    return false;
                }
            } elseif ($part < 0 or $part > 255) {
                return false;
            }
        }

        return true;
    }

    public static function htmlentities(string $value, ?array $replaces = null): string
    {
        if (!$replaces) {
            $replaces = [
                '"' => '&quot;',
                "'" => '&apos;',
                '<' => '&lt;',
                '>' => '&gt;',
            ];
        }

        return str_replace(array_keys($replaces), array_values($replaces), $value);
    }
}
