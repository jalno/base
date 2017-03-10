<?php
namespace packages\base\date;
class gregorian implements date_interface{
	public static function format($format ,$timestamp = null){
		return date($format, $timestamp);
	}
	public static function strtotime($time,$now = null){
		return strtotime($time, $now);
	}
	public static function mktime($hour = null, $minute = null, $second = null , $month = null, $day = null, $year = null){
		return mktime($hour, $minute, $second , $month, $day, $year);
	}
}
