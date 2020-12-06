<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => array(
		'type' => 'mysql',
		'host' => 'localhost',
		'user' => '',
		'pass' => '',
		'dbname' => ''
	),
	'packages.base.session' => array(
		'handler' => 'php',//cache,DB,php
		'ip' => true,
	),
	'packages.base.translator.defaultlang' => 'fa_IR',
	'packages.base.translator.changelang' => 'uri',//uri,parameter
	'packages.base.translator.changelang.type' => 'short',//short, complete
	'packages.base.safe_referers' => array(),
	"packages.base.router.defaultDomain" => "*"
);
