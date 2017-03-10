<?php
namespace packages\base;
use \packages\base\log;
use \packages\base\date\date_interface;
use \packages\base\date\calendarNotExist;

class date implements date_interface{
	static protected $calendar;
	public static function setCanlenderName($name){
		$log = log::getInstance();
		$classname = __NAMESPACE__.'\\date\\'.$name;
		$log->debug("looking for",$classname,"calendar");
		if(class_exists($classname)){
			$log->reply("found");
			self::$calendar = $name;
		}else{
			$log->reply()->fatal("Notfound");
			throw new calendarNotExist($name);
		}
	}
	public static function getCanlenderName(){
		return self::$calendar;
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
	public static function mktime($hour = null, $minute = null, $second = null , $month = null, $day = null, $year = null){
		if(!self::$calendar){
			self::setDefaultcalendar();
		}
		$now = explode("/", self::format("Y/m/d/H/i/s"));
		if($year === null){
			$year = $now[0];
		}
		if($day === null){
			$day = $now[2];
		}
		if($month === null){
			$month = $now[1];
		}
		if($hour === null){
			$hour = $now[3];
		}
		if($minute === null){
			$minute = $now[4];
		}
		if($second === null){
			$second = $now[5];
		}
		if(self::$calendar){
			return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "mktime"), array($hour, $minute, $second, $month, $day, $year));
		}
	}
	public static function time(){
		return time();
	}
	public static function setDefaultcalendar(){
		$log = log::getInstance();
		$defaultOption = array(
			'calendar' => 'gregorian'
		);
		$log->debug("looking for packages.base.date option");
		if(($option = options::load('packages.base.date')) !== false){
			$log->reply($option);
			$foption = array_replace_recursive($defaultOption, $option);
			$log->debug("set calendar to",$foption['calendar']);
			self::setCanlenderName($foption['calendar']);
		}else{
			$log->debug("set calendar to",$defaultOption['calendar']);
			self::setCanlenderName($defaultOption['calendar']);
		}
	}
}
