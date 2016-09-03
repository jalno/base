<?php
namespace packages\base;
class cli{
	public static $request = array();
	public static $process = array();
	public static function set(){
		self::$request['parameters'] = self::getParameters($_SERVER['argv']);
		self::$process['pid'] = getmypid();
	}
	static function getParameter($name){
		if(isset(self::$request['parameters'][$name])){
			return(self::$request['parameters'][$name]);
		}
		return(null);
	}
	/**
	 * to manage arguments on cli use
	 * @return array
	 */
    public static function getParameters($params){
        $return = array();
        for($x = 0;$x!=count($params);$x++){
            if($x == 0)continue;
            if(substr($params[$x], 0, 2) == '--'){
                $temp = explode('=', $params[$x], 2);
                $temp[0] = substr($temp[0], 2);
                $return[$temp[0]] = isset($temp[1]) ? trim($temp[1]) : true;
            }elseif(substr($params[$x], 0, 1) == '-'){
                $temp[0] = substr($params[$x], 1,1);
                $temp[1] = trim(substr($params[$x], 2));
                if(!$temp[1]) $temp[1] = true;
                $return[$temp[0]] = $temp[1];
            }
        }
        return($return);
    }
}
