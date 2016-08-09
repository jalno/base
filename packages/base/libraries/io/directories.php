<?php
namespace packages\base\IO;
function mkdir($pathname, $recursive = false, $mode = 0755){
	return \mkdir($pathname, $mode, $recursive);
}
?>
