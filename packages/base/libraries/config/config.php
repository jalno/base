<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => array(
		'type' => 'mysql',
		'host' => '127.0.0.1',
		'user' => 'root',
		'pass' => 'yeganemehr',
		'dbname' => 'jeyserver_mainsite'
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
	'packages.base.translator.defaultlang' => 'fa_IR',
	'packages.base.translator.changelang' => 'uri',//uri,parameter
	'packages.base.translator.changelang.type' => 'short',//short, complete
	'packages.base.safe_referers' => array(),
	'packages.userpanel.register' => array(
		'enable' => true,
		'type' => 3
	),
	'packages.userpanel.usertypes.guest' => 2,
	'packages.base.translator.defaultlang' => 'fa_IR',
	'packages.userpanel.date' => array(
		'calendar' => 'jdate'
	),
);
?>
