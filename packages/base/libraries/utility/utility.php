<?php
namespace packages\base\utility;
function array_column($input , $column_key,  $index_key = null){
	if(isset($input[0]) and is_object($input[0])){
		$return = array();
		foreach($input as $key => $val){
			if(isset($val->$column_key)){
				if($index_key){
					$return[$val->$index_key] = $val->$column_key;
				}else{
					$return[$key] = $val->$column_key;
				}
			}
		}
		return $return;
	}else{
		return \array_column($input, $column_key, $index_key);
	}
}
