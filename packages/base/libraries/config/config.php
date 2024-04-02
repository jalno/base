<?php

namespace packages\base;

$options = [
    'packages.base.env' => getenv('JALNO_ENV') ?: 'debug',
    'packages.base.debug-ip' => getenv('JALNO_DEBUG_IP') ? explode(',', getenv('JALNO_DEBUG_IP')) : [
        'cli',
        '127.0.0.1',
    ],
    'packages.base.loader.db' => ('disabled' != getenv('JALNO_DB')) ? [
        'type' => 'mysql',
        'host' => getenv('JALNO_DB_HOST') ?: 'localhost',
        'user' => getenv('JALNO_DB_USER') ?: 'root',
        'pass' => getenv('JALNO_DB_PASSWORD') ?: '',
        'dbname' => getenv('JALNO_DB_NAME') ?: 'jalno',
    ] : null,
    'packages.base.translator.defaultlang' => getenv('JALNO_LANG') ?: 'fa_IR',
    'packages.base.router.defaultDomain' => '*',
];
