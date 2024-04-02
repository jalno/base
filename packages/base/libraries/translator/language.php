<?php

namespace packages\base\translator;

use packages\base\Date;
use packages\base\Exception;
use packages\base\json;
use packages\base\translator;

class language
{
    private $code;
    private $rtl;
    private $phrases = [];
    private $date = [
        'calendar' => '',
        'formats' => [],
    ];

    public function __construct($code)
    {
        $this->setCode($code);
    }

    public function setCode($code)
    {
        if (translator::is_validCode($code)) {
            $this->code = $code;
        } else {
            throw new InvalidLangCode();
        }
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setRTL($value)
    {
        $this->rtl = $value;
    }

    public function loadByFile($file)
    {
        if (is_file($file) and is_readable($file)) {
            $file = file_get_contents($file);
            if (($file = json\decode($file)) !== false) {
                if (isset($file['rtl']) and is_bool($file['rtl'])) {
                    $this->setRTL($file['rtl']);
                }
                if (isset($file['date']) and is_array($file['date'])) {
                    if (isset($file['date']['calendar'])) {
                        $this->setCalendar($file['date']['calendar']);
                    }
                    if (isset($file['date']['formats']) and is_array($file['date']['formats'])) {
                        foreach ($file['date']['formats'] as $key => $format) {
                            $this->setDateFormat($key, $format);
                        }
                    }
                }
                if (isset($file['phrases'])) {
                    foreach ($file['phrases'] as $key => $val) {
                        if (is_string($val) and is_string($key) and !isset($this->phrases[$key])) {
                            $this->addPhrase($key, $val);
                        }
                    }
                }
            } else {
                echo json_last_error_msg();
                exit;
                throw new InvalidJson();
            }

            return true;
        }

        return false;
    }

    public function addPhrase($key, $phrase)
    {
        $this->phrases[$key] = $phrase;

        return true;
    }

    public function getPhrase($key)
    {
        if (isset($this->phrases[$key])) {
            return $this->phrases[$key];
        }

        return false;
    }

    public function getPhrases()
    {
        return $this->phrases;
    }

    public function trans($key, array $params = [])
    {
        if (($phrase = $this->getPhrase($key)) != false) {
            if (preg_match_all('/[^\\\\]?{([^}]+)}/', $phrase, $matches)) {
                $replaces = [];
                foreach ($matches[1] as $index => $key) {
                    $f = substr($matches[0][$index], 0, 1);
                    $replaces[] = ('{' != $f ? $f : '').(isset($params[$key]) ? $params[$key] : '');
                }
                $phrase = str_replace($matches[0], $replaces, $phrase);
            }

            return $phrase;
        }

        return false;
    }

    public function isRTL()
    {
        return $this->rtl;
    }

    public function setCalendar(string $calendar)
    {
        $this->date['calendar'] = $calendar;
    }

    public function setDateFormat(string $key, string $format)
    {
        if (!isset(Date::$presetsFormats[$key])) {
            throw new Exception("'{$key}' is not a presets formats. allowed presets formats is: ".json\encode(Date::$presetsFormats));
        }
        $this->date['formats'][$key] = $format;
    }

    public function getCalendar(): ?string
    {
        return $this->date['calendar'] ?? null;
    }

    public function getDateFormat(string $key): ?string
    {
        return $this->date['formats'][$key] ?? null;
    }

    public function getDateFormats(): array
    {
        return $this->date['formats'];
    }
}
