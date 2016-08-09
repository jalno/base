<?php
namespace packages\base\access\package;
function controller(&$package,$controller){
	return $package->checkPermission($controller);
}
?>
