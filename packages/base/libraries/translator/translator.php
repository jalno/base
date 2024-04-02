<?php

namespace packages\base;

use packages\base\translator\InvalidLangCode;
use packages\base\translator\language;

class translator
{
    public static $allowlangs = [
        'en', 'aa', 'ab', 'af', 'am', 'ar', 'as', 'ay', 'az', 'ba', 'be', 'bg', 'bh', 'bi', 'bn', 'bo', 'br', 'ca', 'co', 'cs', 'cy', 'da', 'de', 'dz', 'el',
        'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fj', 'fo', 'fr', 'fy', 'ga', 'gd', 'gl', 'gn', 'gu', 'ha', 'hi', 'hr', 'hu', 'hy', 'ia', 'ie', 'ik', 'in', 'is',
        'it', 'iw', 'ja', 'ji', 'jw', 'ka', 'kk', 'kl', 'km', 'kn', 'ko', 'ks', 'ku', 'ky', 'la', 'ln', 'lo', 'lt', 'lv', 'mg', 'mi', 'mk', 'ml', 'mn', 'mo',
        'mr', 'ms', 'mt', 'my', 'na', 'ne', 'nl', 'no', 'oc', 'om', 'pa', 'pl', 'ps', 'pt', 'qu', 'rm', 'rn', 'ro', 'ru', 'rw', 'sa', 'sd', 'sg', 'sh', 'si',
        'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'ss', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'ti', 'tk', 'tl', 'tn', 'to', 'tr', 'ts', 'tt', 'tw',
        'uk', 'ur', 'uz', 'vi', 'vo', 'wo', 'xh', 'yo', 'zh', 'zu',
    ];
    public static $countries = [
        'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM',
        'BT', 'BO', 'BA', 'BW', 'BV', 'BR', 'IO', 'BN', 'BG', 'BF', 'BI', 'KH', 'CM', 'CA', 'CV', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CG',
        'CD', 'CK', 'CR', 'CI', 'HR', 'CU', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF',
        'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS',
        'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI',
        'LT', 'LU', 'MO', 'MK', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM',
        'NA', 'NR', 'NP', 'NL', 'AN', 'NC', 'NZ', 'NI', 'NE', 'NG', 'NU', 'NF', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PL',
        'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SK', 'SI',
        'SB', 'SO', 'ZA', 'GS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SZ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK', 'TO', 'TT', 'TN', 'TR', 'TM',
        'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU', 'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW',
    ];
    private static $lang;
    private static $langs = [];

    public static function setLang($code)
    {
        if (isset(self::$langs[$code])) {
            self::$lang = $code;
        } else {
            throw new InvalidLangCode();
        }
    }

    public static function getDefaultLang()
    {
        $defaultlang = options::get('packages.base.translator.defaultlang');

        return $defaultlang;
    }

    public static function getDefaultShortLang()
    {
        return substr(self::getDefaultLang(), 0, 2);
    }

    public static function getAvailableLangs()
    {
        return array_keys(self::$langs);
    }

    public static function getLangs()
    {
        return self::$langs;
    }

    public static function addLang($code)
    {
        if (!isset(self::$langs[$code])) {
            if (!self::is_validCode($code)) {
                throw new InvalidLangCode();
            }
            self::$langs[$code] = new language($code);
        }

        return self::$langs[$code];
    }

    public static function getLang($code = null)
    {
        if ($code) {
            return isset(self::$langs[$code]) ? self::$langs[$code] : false;
        } else {
            return self::$langs[self::$lang];
        }
    }

    public static function getCodeLang($code = null)
    {
        if ($code) {
            return $code;
        } else {
            return self::$lang;
        }
    }

    public static function getShortCodeLang($code = null)
    {
        if ($code) {
            return substr($code, 0, 2);
        } else {
            return substr(self::$lang, 0, 2);
        }
    }

    public static function trans($key, array $params = [])
    {
        if (self::$lang and isset(self::$langs[self::$lang])) {
            return self::$langs[self::$lang]->trans($key, $params);
        }
    }

    public static function import(language $lang)
    {
        $code = $lang->getCode();
        if (!isset(self::$langs[$code])) {
            self::$langs[$code] = $lang;
        } else {
            $phrases = $lang->getPhrases();
            self::$langs[$code]->setRTL($lang->isRTL());
            if ($calendar = $lang->getCalendar()) {
                self::$langs[$code]->setCalendar($calendar);
            }
            foreach ($lang->getDateFormats() as $key => $format) {
                self::$langs[$code]->setDateFormat($key, $format);
            }
            try {
                foreach ($phrases as $key => $phrase) {
                    self::$langs[$code]->addPhrase($key, $phrase);
                }
            } catch (translator\PhraseAlreadyExists $e) {
            }
        }
    }

    public static function is_validCode($code)
    {
        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/', $code, $matches)) {
            if (in_array($matches[1], self::$allowlangs) and in_array($matches[2], self::$countries)) {
                return true;
            }
        }

        return false;
    }

    public static function is_shortCode($code)
    {
        return in_array($code, self::$allowlangs);
    }
}
