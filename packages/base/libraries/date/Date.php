<?php
namespace packages\base;

use DateTimeZone;
use packages\base\date\date_interface;
use packages\base\date\CalendarNotExist;

class Date implements date_interface {
	public static $presetsFormats = array(
		"Q" => "m/d/Y",
		"q" => "n/j/Y",
		"QQ" => "F d Y",
		"qq" => "M d Y",
		"QQQ" => "F d Y g:i A",
		"qqq" => "M d Y g:i A",
		"QQQQ" => "l, F d Y g:i A",
		"qqqq" => "D, M d Y g:i A",
		"QT" => "g:i A",
		"QTS" => "g:i:s A",
	);
	public static function setPresetsFormat(string $key, string $format) {
		if (!isset(self::$presetsFormats[$key])) {
			throw new Exception("'{$key}' is not a presets formats. allowed presets formats is: " . json\encode(self::$presetsFormats));
		}
		self::$presetsFormats[$key] = $format;
	}
	static protected $calendar;
	static protected $inited = false;
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
	public static function setTimeZone(string $timezone) {
		$log = log::getInstance();
		$log->debug("check given timezone (" . $timezone . ") is valid?");
		if (!in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL))) {
			$log->reply()->fatal("is not valid");
			throw new Date\TimeZoneNotValid($timezone);
		} else {
			$log->reply("is valid");
		}
		date_default_timezone_set($timezone);
	}
	public static function getTimeZone(): string {
		self::init();
		return date_default_timezone_get();
	}
	public static function format($format ,$timestamp = null){
		self::init();
		if($timestamp === null){
			$timestamp = self::time();
		}
		$presetsFormats = array_reverse(self::$presetsFormats);
		$format = str_replace(array_keys($presetsFormats), array_values($presetsFormats), $format);
		return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "format"), array($format, $timestamp));
	}
	public static function strtotime($time,$now = null){
		self::init();
		if($now === null){
			$now = self::time();
		}
		return call_user_func_array(array(__NAMESPACE__.'\\date\\'.self::$calendar, "strtotime"), array($time, $now));
	}

	public static function getFirstDayOfWeek(): int {
		self::init();
		return call_user_func(array(__NAMESPACE__.'\\date\\'.self::$calendar, "getFirstDayOfWeek"));
	}
	public static function getWeekDay(int $day): ?int {
		self::init();
		return call_user_func(array(__NAMESPACE__.'\\date\\'.self::$calendar, "getWeekDay"), $day);
	}
	public static function mktime($hour = null, $minute = null, $second = null , $month = null, $day = null, $year = null){
		self::init();
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

	/**
	 * @return int
	 */
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
			foreach ($lang->getDateFormats() as $key => $format) {
				self::setPresetsFormat($key, $format);
			}
		} else {
			$log->debug("looking for packages.base.date option");
			if (($option = options::load('packages.base.date')) !== false) {
				$log->reply($option);
				$defaultOption = array_replace_recursive($defaultOption, $option);
				$log->debug("set calendar to",$defaultOption['calendar']);
				self::setCanlenderName($defaultOption['calendar']);
			} else{
				$log->reply("Not defined");
			}
		}
		$log->debug("set calendar to",$defaultOption['calendar']);
		self::setCanlenderName($defaultOption['calendar']);
	}

	public static function setDefaultTimeZone() {
		$log = log::getInstance();
		$defaultOption = array();
		$log->debug("looking for packages.base.date option");
		$option = Options::get('packages.base.date');
		if ($option !== false) {
			$log->reply("found");
		}
		if (!isset($defaultOption['timezone'])) {
			return;
		}
		$log->debug("set timezone to", $defaultOption['timezone']);
		self::setTimeZone($defaultOption['timezone']);
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
	public static function init() {
		if (self::$inited) {
			return;
		}
		self::setDefaultTimeZone();
		if(!self::$calendar){
			self::setDefaultcalendar();
		}
		if(!self::$calendar){
			throw new Date\NoCalendarException();
		}
		self::$inited = true;
	}
}
