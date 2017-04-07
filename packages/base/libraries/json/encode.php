<?php
namespace packages\base\json;
const PRETTY = JSON_PRETTY_PRINT;
const FORCE_OBJECT = JSON_FORCE_OBJECT;
function encode($value, $options = 0, $depth = 512){
	if($options == 0 and defined('JSON_UNESCAPED_UNICODE'))$options =  JSON_UNESCAPED_UNICODE;
	if(phpversion() >= '5.4.0'){
			return \json_encode($value, $options);
	}else{
		switch ($type = \gettype($value)) {
	        case 'NULL':
	            return 'null';
	        case 'boolean':
	            return ($value ? 'true' : 'false');
	        case 'integer':
	        case 'double':
	        case 'float':
	            return $value;
	        case 'string':
	            return '"' . \addslashes($value) . '"';
	        case 'object':
	            $value = \get_object_vars($value);
	        case 'array':
	            $output_index_count = 0;
	            $output_indexed = array();
	            $output_associative = array();
	            foreach ($value as $key => $value) {
	                $output_indexed[] = encode($value);
	                $output_associative[] = \json_encode($key) . ':' . encode($value);
	                if ($output_index_count !== NULL && $output_index_count++ !== $key) {
	                    $output_index_count = NULL;
	                }
	            }
	            if ($output_index_count !== NULL) {
	                return '[' . \implode(',', $output_indexed) . ']';
	            } else {
	                return '{' . \implode(',', $output_associative) . '}';
	            }
	        default:
	            return ''; // Not supported
	    }
	
	}
}
?>
