<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => array(
		'type' => 'mysql',
		'host' => '127.0.0.1',
		'user' => 'root',
		'pass' => 'yeganemehr',
		'dbname' => 'webuiler_slave'
	),
	'packages.base.frontend.theme' => 'w3school',
	'packages.base.session' => array(
		'handler' => 'file',//cache,mysql,php
		'cookie' => array(
			//'name' => 'SESSID2',
			'expire' => 0,
			//'domain' => '',
			//'path'=> '/',
			//'httponly' => false,
			//'sslonly' => false,
		),
		'ip' => true,
	),
	'packages.userpanel.register' => array(
		'enable' => true,
	),
	'packages.base.translator.defaultlang' => 'fa_IR'
);
?>
