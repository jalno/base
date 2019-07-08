CREATE TABLE `base_cache` (
    `name` varchar(255) NOT NULL,
    `value` text NOT NULL,
    `expire_at` int(10) unsigned NOT NULL,
    PRIMARY KEY (`name`),
    KEY `expire_at` (`expire_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `base_processes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) COLLATE utf8_persian_ci NOT NULL,
    `pid` int(11) DEFAULT NULL,
    `start` int(11) DEFAULT NULL,
    `end` int(11) DEFAULT NULL,
    `parameters` text COLLATE utf8_persian_ci,
    `response` text COLLATE utf8_persian_ci,
    `progress` int(11) DEFAULT NULL,
    `status` tinyint(4) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;

CREATE TABLE `options` (
    `name` varchar(255) NOT NULL,
    `value` text NOT NULL,
    `autoload` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `options` (`name`, `value`, `autoload`) VALUES
('packages.base.routing.www', 'nowww', 1),
('packages.base.routing.scheme', 'http', 1);