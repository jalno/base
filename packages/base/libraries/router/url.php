<?php
namespace packages\base;
function url($page = '',$parameters = array()){
	return "/".$page.($parameters ? '?'.http_build_query($parameters) : '');
}
?>
