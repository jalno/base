<?php
namespace packages\base;
$options = array(
	'packages.base.loader.db' => (getenv("JALNO_DB") != "disabled") ? array(
		'type' => 'mysql',
		'host' => getenv("JALNO_DB_HOST") ?: "localhost",
		'user' => getenv("JALNO_DB_USER") ?: "root",
		'pass' => getenv("JALNO_DB_PASSWORD") ?: "",
		'dbname' => getenv("JALNO_DB_NAME") ?: "jalno"
	) : null,
	'packages.base.translator.defaultlang' => 'fa_IR',
	'packages.base.translator.changelang' => 'uri',//uri,parameter
	'packages.base.translator.changelang.type' => 'short',//short, complete
	'packages.base.safe_referers' => array(),
	"packages.base.router.defaultDomain" => "*"
);
