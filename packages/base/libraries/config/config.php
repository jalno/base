<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => array(
		'default' => array(
			'type' => 'mysql',
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'jeyserver',
			'dbname' => 'araddoc_jalno',
		),
		'aradsites' => array(
			'type' => 'mysql',
			'host' => 'localhost',
			'user' => 'root',
			'pass' => 'jeyserver',
			'dbname' => 'araduser_jalno'
		),
	),
	'packages.base.session' => array(
		'handler' => 'php',//cache,mysql,php
		'ip' => false,
	),
	'packages.base.translator.defaultlang' => 'fa_IR',
	/* 'packages.base.translator.active.langs' => array(
		"en_US",
	), */
	'packages.base.translator.changelang' => 'uri',//uri,parameter
	'packages.base.translator.changelang.type' => 'short',//short, complete
	'packages.base.safe_referers' => array()
);
