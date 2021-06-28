<?php
namespace packages\base;
$options = array(
	'packages.base.env' => getenv("JALNO_ENV") ?: "debug",
	'packages.base.debug-ip' => getenv("JALNO_DEBUG_IP") ? explode(",", getenv("JALNO_DEBUG_IP")): ["127.0.0.1", "cli"],
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
