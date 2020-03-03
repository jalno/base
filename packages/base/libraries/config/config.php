<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => array(
		'type' => 'mysql',
		'host' => 'localhost',
		'user' => 'root',
		'pass' => 'Jeyserver5*',
		'dbname' => 'jeyserver_mainsite',
		'port' => '',
	),
	'packages.base.session' => array(
		'handler' => 'php',//cache,mysql,php
		'ip' => false,
	),
	'packages.base.translator.defaultlang' => 'fa_IR',
	'packages.base.translator.changelang' => 'uri',//uri,parameter
	'packages.base.translator.changelang.type' => 'short',//short, complete
	'packages.base.safe_referers' => array()
);
