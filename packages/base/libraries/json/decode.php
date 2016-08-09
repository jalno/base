<?php
namespace packages\base\json;
function decode($json, $assoc = true, $depth=512, $options=0){
	$ver = \phpversion();
	if($ver >= '5.4.0'){
		return \json_decode($json, $assoc, $depth, $options);
	}elseif($ver >= '5.3.0'){
		return \json_decode($json, $assoc, $depth);
	}
}
?>
