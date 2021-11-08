<?php
namespace packages\base\date;
class gregorian implements date_interface{
	public static $weekDays = array(1, 2, 3, 4, 5, 6, 0);
	public static function format($format ,$timestamp = null){
		if ($timestamp === null) {
			$timestamp = time();
		}
		return date($format, $timestamp);
	}
	public static function strtotime($time,$now = null){
		if ($now === null) {
			$now = time();
		}
		return strtotime($time, $now);
	}

	public static function getFirstDayOfWeek(): int {
		return self::$weekDays[0];
	}
	public static function getWeekDay(int $day): ?int {
		$key = array_search($day, self::$weekDays);
		return $key !== false ? $key : null;
	}
	public static function mktime($hour = null, $minute = null, $second = null , $month = null, $day = null, $year = null){
		if ($hour === null) {
			$hour = date("H");
		}
		if ($minute === null) {
			$minute = date("i");
		}
		if ($second === null) {
			$second = date("s");
		}
		if ($month === null) {
			$month = date("n");
		}
		if ($day === null) {
			$day = date("j");
		}
		if ($year === null) {
			$year = date("Y");
		}
		return mktime($hour, $minute, $second , $month, $day, $year);
	}
}
