<?php
namespace packages\base;
use \packages\base\log;
use \packages\base\date\date_interface;
use \packages\base\date\calendarNotExist;

class date implements date_interface {
	public static $presetsFormats = array(
		"LT" => "g:i A",
		"LTS" => "g:i:s A",
		"L" => "m/d/Y",
		"l" => "n/j/Y",
		"LL" => "F n Y",
		"ll" => "M n Y",
		"LLL" => "F n Y g:i A",
		"lll" => "M n Y g:i A",
		"LLLL" => "l, F n Y g:i A",
		"llll" => "D, M n Y g:i A",
	);
	public static function setPresetsFormat(string $key, string $format) {
		if (!isset(self::$presetsFormats[$key])) {
			throw new Exception("'{$key}' is not a presets formats. allowed presets formats is: " . json\encode(self::$presetsFormats));
		}
		self::$presetsFormats[$key] = $format;
	}
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
			if (isset(self::$presetsFormats[$format])) {
				$format = self::$presetsFormats[$format];
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
		$log->debug("looking for active language date calendar");
		$lang = Translator::getLang();
		if ($lang and $calendar = $lang->getCalendar()) {
			$log->reply($calendar);
			$defaultOption["calendar"] = $calendar;
		} else {
			$log->debug("looking for packages.base.date option");
			if (($option = options::load('packages.base.date')) !== false) {
				$log->reply($option);
				$defaultOption = array_replace_recursive($defaultOption, $option);
				$log->debug("set calendar to",$foption['calendar']);
				self::setCanlenderName($foption['calendar']);
			} else{
				$log->reply("Not defined");
			}
		}
		$log->debug("set calendar to",$defaultOption['calendar']);
		self::setCanlenderName($defaultOption['calendar']);
	}
	public static function relativeTime(int $time, string $format = 'short'):string{
		$now = self::time();
		$mine = $time - $now;
		if($mine == 0){
			return translator::trans('date.relatively.now');
		}
		$steps = ['y', 'm', 'w', 'd', 'h', 'i', 's'];
		if($format == 'short'){
			$format = 'y';
		}elseif($format == 'long'){
			$format = 's';
		}elseif(!in_array($format, $steps)){
			throw new \TypeError('wrong format, allowed: y, m, w, d, h, i, s');
		}
		$maxStep = array_search($format, $steps);
		$abs = abs($mine);
		$expr = [];
		foreach([['years', 31536000], ['months', 2592000], ['weeks', 604800], ['days', 86400], ['hours', 3600], ['minutes', 60]] as $key => $item){
			$matched = false;
			if($abs >= $item[1]){
				$matched = true;
			}
			$isLast = (count($expr) + $matched and $key >= $maxStep);
			if($matched){
				$number = $isLast ? ceil($abs / $item[1]) : floor($abs / $item[1]);
				$expr[] = translator::trans('date.relatively.'.$item[0], ['number' => $number ]);
				$abs = $abs % $item[1];
			}
			if($isLast){
				break;
			}
		}
		if($abs and (!$expr or $maxStep == 6)){
			$expr[] = translator::trans('date.relatively.seconds', ['number' => $abs]);
		}
		$expr_text = $expr[0];
		$count = count($expr);
		for($x = 1;$x < $count;$x++){
			$expr_text = translator::trans('date.relatively.and', array(
				'expr1' => $expr_text,
				'expr2' => $expr[$x]
			));
		}
        if($mine < 0){
           return translator::trans('date.relatively.ago', ['expr' => $expr_text]);
		}else{
           return translator::trans('date.relatively.later', ['expr' => $expr_text]);
		}
	}
}
