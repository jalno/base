<?php

namespace packages\base;

use Illuminate\Translation\Translator as TranslationTranslator;

class Translator
{
    public static array $allowlangs = [
        'en', 'aa', 'ab', 'af', 'am', 'ar', 'as', 'ay', 'az', 'ba', 'be', 'bg', 'bh', 'bi', 'bn', 'bo', 'br', 'ca', 'co', 'cs', 'cy', 'da', 'de', 'dz', 'el',
        'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fj', 'fo', 'fr', 'fy', 'ga', 'gd', 'gl', 'gn', 'gu', 'ha', 'hi', 'hr', 'hu', 'hy', 'ia', 'ie', 'ik', 'in', 'is',
        'it', 'iw', 'ja', 'ji', 'jw', 'ka', 'kk', 'kl', 'km', 'kn', 'ko', 'ks', 'ku', 'ky', 'la', 'ln', 'lo', 'lt', 'lv', 'mg', 'mi', 'mk', 'ml', 'mn', 'mo',
        'mr', 'ms', 'mt', 'my', 'na', 'ne', 'nl', 'no', 'oc', 'om', 'pa', 'pl', 'ps', 'pt', 'qu', 'rm', 'rn', 'ro', 'ru', 'rw', 'sa', 'sd', 'sg', 'sh', 'si',
        'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'ss', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'ti', 'tk', 'tl', 'tn', 'to', 'tr', 'ts', 'tt', 'tw',
        'uk', 'ur', 'uz', 'vi', 'vo', 'wo', 'xh', 'yo', 'zh', 'zu',
    ];
    public static array $countries = [
        "ab" => "RU",
        "aa" => "ET",
        "af" => "ZA",
        "ak" => "GH",
        "sq" => "AL",
        "am" => "ET",
        "ar" => "EG",
        "an" => "ES",
        "hy" => "AM",
        "as" => "IN",
        "av" => "RU",
        "ae" => "ET",
        "ay" => "BO",
        "az" => "AZ",
        "bm" => "ML",
        "ba" => "RU",
        "eu" => "ES",
        "be" => "BY",
        "bn" => "BD",
        "bi" => "CM",
        "bs" => "BA",
        "br" => "FR",
        "bg" => "BG",
        "my" => "MM",
        "ca" => "ES",
        "ch" => "GU",
        "ce" => "RU",
        "ny" => "MW",
        "zh" => "CN",
        "cu" => "BY",
        "cv" => "RU",
        "kw" => "GB",
        "co" => "CO",
        "cr" => "CA",
        "hr" => "HR",
        "cs" => "CZ",
        "da" => "DK",
        "dv" => "MV",
        "nl" => "NL",
        "dz" => "BT",
        "en" => "US",
        "et" => "EE",
        "ee" => "GH",
        "fo" => "FO",
        "fj" => "FJ",
        "fi" => "FI",
        "fr" => "FR",
        "fy" => "NL",
        "ff" => "SN",
        "gd" => "GB",
        "gl" => "ES",
        "lg" => "UG",
        "ka" => "GE",
        "de" => "DE",
        "el" => "GR",
        "kl" => "GL",
        "gn" => "PY",
        "gu" => "IN",
        "ht" => "HT",
        "ha" => "NG",
        "he" => "IL",
        "hz" => "NA",
        "hi" => "IN",
        "ho" => "PG",
        "hu" => "HU",
        "is" => "IS",
        "ig" => "NG",
        "id" => "ID",
        "iu" => "CA",
        "ik" => "CA",
        "ga" => "IE",
        "it" => "IT",
        "ja" => "JP",
        "jv" => "ID",
        "kn" => "IN",
        "kr" => "NG",
        "ks" => "IN",
        "kk" => "KZ",
        "km" => "KH",
        "ki" => "KE",
        "rw" => "RW",
        "ky" => "KG",
        "kv" => "RU",
        "kg" => "CO",
        "ko" => "KR",
        "kj" => "AO",
        "ku" => "IQ",
        "lo" => "LA",
        "la" => "VA",
        "lv" => "LV",
        "li" => "NL",
        "ln" => "CD",
        "lt" => "LT",
        "lu" => "CD",
        "lb" => "LU",
        "mk" => "MK",
        "mg" => "MG",
        "ms" => "MY",
        "ml" => "IN",
        "mt" => "MT",
        "gv" => "IM",
        "mi" => "NZ",
        "mr" => "IN",
        "mh" => "MH",
        "mn" => "MN",
        "na" => "NA",
        "nv" => "US",
        "nd" => "ZW",
        "nr" => "ZA",
        "ng" => "NA",
        "ne" => "NP",
        "no" => "NO",
        "nb" => "NO",
        "nn" => "NO",
        "oc" => "FR",
        "oj" => "CA",
        "or" => "IN",
        "om" => "ET",
        "os" => "RU",
        "pi" => "FJ",
        "ps" => "AF",
        "fa" => "IR",
        "pl" => "PL",
        "pt" => "BR",
        "pa" => "PK",
        "qu" => "PE",
        "ro" => "RO",
        "rm" => "CH",
        "rn" => "BI",
        "ru" => "RU",
        "se" => "NO",
        "sm" => "SM",
        "sg" => "SG",
        "sa" => "IN",
        "sc" => "IT",
        "sr" => "RS",
        "sn" => "ZW",
        "sd" => "PK",
        "si" => "LK",
        "sk" => "SK",
        "sl" => "SI",
        "so" => "SO",
        "st" => "ZA",
        "es" => "ES",
        "su" => "ID",
        "sw" => "TZ",
        "ss" => "ZA",
        "sv" => "SE",
        "tl" => "PH",
        "ty" => "PF",
        "tg" => "TJ",
        "ta" => "IN",
        "tt" => "RU",
        "te" => "IN",
        "th" => "TH",
        "bo" => "CN",
        "ti" => "ET",
        "to" => "TO",
        "ts" => "ZA",
        "tn" => "BW",
        "tr" => "TR",
        "tk" => "TM",
        "tw" => "TW",
        "ug" => "CN",
        "uk" => "UA",
        "ur" => "PK",
        "uz" => "UZ",
        "ve" => "ZA",
        "vi" => "VN",
        "wa" => "BE",
        "cy" => "CY",
        "wo" => "SN",
        "xh" => "ZA",
        "ii" => "CN",
        "yi" => "IS",
        "yo" => "NG",
        "za" => "ZA",
        "zu" => "ZA"
    ];


    /**
     * @deprecated use `app()->getLocale()` instead
     */
    public static function getShortCodeLang(): string
    {
        return app()->getLocale();
    }


    public static function is_validCode(string $code): bool
    {
        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/', $code, $matches)) {
            if (in_array($matches[1], self::$allowlangs) and in_array($matches[2], self::$countries)) {
                return true;
            }
        }

        return false;
    }

    public static function is_shortCode(string $code): bool
    {
        return in_array($code, self::$allowlangs);
    }

    /**
     * @return string[]
     */
    public static function getAvailableLangs(): array
    {
        $codes = (array) Options::get("packages.base.translator.active.lang");
        if (!$codes) {
            $codes = [app()->getLocale()];
        }
        return $codes;
    }

    public static function isRTL(?string $code = null): bool {
        if ($code === null) {
            $code = app()->getLocale();
        }
        return in_array($code, ["ar", "dv", "fa","ha","he","ks","ku","ps", "ur", "yi"]);
    }
}
