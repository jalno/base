<?php
namespace packages\base;
use \packages\base\date\date_interface;
use \packages\base\date\calendarNotExist;

class date implements date_interface{
	static protected $calendar;
	public static function setCanlenderName($name){
		if(class_exists(__NAMESPACE__.'\\date\\'.$name)){
			self::$calendar = $name;
		}else{
			throw new calendarNotExist($name);
		}
	}
	public static function format($format ,$timestamp = null){
		if(!self::$calendar){
			self::setDefaultcalendar();
		}
		if(self::$calendar){
			if($timestamp === null){
				$timestamp = self::time();
			}
			return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "format"), array($format, $timestamp));
		}
	}
	public static function strtotime($time,$now = null){
		if(!self::$calendar){
			self::setDefaultcalendar();
		}
		if(self::$calendar){
			if($now === null){
				$now = self::time();
			}
			return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "strtotime"), array($time, $now));
		}
	}
	public static function mktime($hour = null, $minute = null, $second = null , $month = null, $day = null, $year = null, $is_dst = -1){
		if(!self::$calendar){
			self::setDefaultcalendar();
		}
		if(self::$calendar){
			return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "mktime"), array($hour, $minute, $second, $month, $day, $year, $is_dst));
		}
	}
	public static function time(){
		return time();
	}
	public static function setDefaultcalendar(){
		$defaultOption = array(
			'calendar' => 'gregorian'
		);
		if(($option = options::load('packages.base.date')) !== false){
			$foption = array_replace_recursive($defaultOption, $option);
			self::setCanlenderName($foption['calendar']);
		}else{
			self::setCanlenderName($defaultOption['calendar']);
		}
	}
}
