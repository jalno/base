<?php

namespace packages\base\Date;

use packages\base\Exception as BaseException;

class CalendarNotExist extends \Exception
{
}
class NoCalendarException extends BaseException
{
}
class TimeZoneNotValid extends BaseException
{
}
