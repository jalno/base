<?php
namespace packages\base\date;
use packages\base\Exception as BaseException;
use \Exception;
class calendarNotExist extends Exception {}
class NoCalendarException extends BaseException {}
class TimeZoneNotValid extends BaseException {}