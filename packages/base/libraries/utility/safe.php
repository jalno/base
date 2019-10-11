<?php
namespace packages\base\utility;
class safe{
	static function string($str){
        $str = trim($str);
        $str = str_replace(array('\\', '\'', '"'), "", $str);
        $str = htmlentities($str , ENT_IGNORE|ENT_SUBSTITUTE 	|ENT_DISALLOWED, 'UTF-8');
        return($str);
    }
    static function number($num, $negative =false){
    	if(preg_match($negative ? "/(-?\d+)/" : "/(\d+)/", $num, $matches)){
    		return((int)$matches[1]);
    	}
    }
    static function date($str){
        $str = trim($str);
        return(preg_match('/(\d{4})\/(\d{2})\/(\d{2})/', $str, $matches) ? array('year' => $matches[1],'month' => $matches[2],'day' => $matches[3]) :  '');
    }
    static function is_date($str){
    	$str = trim($str);
    	if(preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})((\s+)(\d{1,2}))?(:(\d{1,2}))?(:(\d{1,2}))?$/', $str, $matches)){
    		$d = array(
    			'Y' => $matches[1],
    			'm' => $matches[2],
    			'd' => $matches[3],
    		);
    		if(isset($matches[6]) and $matches[6]>=0 and $matches[6]< 24){
    			$d['h'] = $matches[6];
    		}
    		if(isset($matches[8]) and $matches[8]>=0 and $matches[8]< 60){
    			$d['i'] = $matches[8];
    		}
    		if(isset($matches[10]) and $matches[10]>=0 and $matches[8]< 60){
    			$d['s'] = $matches[10];
    		}
    		return $d;
    	}else{
    		return false;
    	}
    }
    static function is_email($address){
        return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
    }
    static function is_cellphone_ir($cellphone){
        if((strlen($cellphone) == 11 and substr($cellphone, 0, 2) == '09') or (strlen($cellphone) == 10 and substr($cellphone, 0, 1) == '9') or (strlen($cellphone) == 12 and substr($cellphone, 0, 3) == '989') or (strlen($cellphone) == 13 and substr($cellphone, 0, 4) == '+989')){
            if(strlen($cellphone) == 10)$sub4 = '0'.substr($cellphone, 0, 3);//913
            elseif(strlen($cellphone) == 11)$sub4 = substr($cellphone, 0, 4);//0913
            elseif(strlen($cellphone) == 12)$sub4 = '0'.substr($cellphone, 2, 3);//98913
            elseif(strlen($cellphone) == 13)$sub4 = '0'.substr($cellphone, 3, 3);//+98913
            $error = false;

            switch($sub4){
            	case('0910'):case('0911'):case('0912'):case('0913'):case('0914'):case('0915'):case('0916'):case('0917'):case('0918'):case('0919'):case('0990'): case('0991')://TCI
            	case('0931')://Spadan
            	case('0932')://Taliya
            	case('0934')://TKC
            	case('0901'):case('0902'):case('0903'):case('0905'): //IranCell - ISim
            	case('0930'):case('0933'):case('0935'):case('0936'):case('0937'):case('0938'):case('0939')://IranCell
            	case('0920'):case('0921'):case('0922')://RighTel
            		$error = false;
            		break;
            	default:
            		$error = true;
            		break;
            }
            return $error ? false:true;
        }else{
            return false;
        }
    }
    static function cellphone_ir($cellphone){
    	if((strlen($cellphone) == 11 and substr($cellphone, 0, 2) == '09') or (strlen($cellphone) == 10 and substr($cellphone, 0, 1) == '9') or (strlen($cellphone) == 12 and substr($cellphone, 0, 3) == '989') or (strlen($cellphone) == 13 and substr($cellphone, 0, 4) == '+989')){
            if(strlen($cellphone) == 10) 	return '98'.$cellphone;//913
            elseif(strlen($cellphone) == 11)return '98'.substr($cellphone, 1);//0913
            elseif(strlen($cellphone) == 12)return $cellphone;//98913
            elseif(strlen($cellphone) == 13)return substr($cellphone, 1);//+98913

        }
        return false;
    }
    static function bool($value){
        return ($value == 'true' or $value == 1);
    }
	static function is_ip4($ip){
		$parts = explode('.',$ip);
		if(count($parts) != 4){
			return false;
		}
		foreach($parts as $key => $part){
			if($key == 0){
				if($part <= 0 or $part > 255){
					return false;
				}
			}elseif($part < 0 or $part > 255){
				return false;
			}
		}
		return true;
	}
	static function htmlentities (string $value): string {
		return str_replace(array('"', "'", "<", ">"), array("&quot;", "&apos;", "&lt;", "&gt;"), $value);
	}
}
