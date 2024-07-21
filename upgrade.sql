--
-- Commit: 46c37f03fdeb31256029a3dc6434e9c59ba8f316
--

CREATE TABLE `base_sessions` (
    `id` varchar(32) NOT NULL,
    `ip` varchar(15) NOT NULL,
    `create_at` int(11) NOT NULL,
    `lastuse_at` int(11) NOT NULL,
    `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
    PRIMARY KEY (`id`),
    KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Commit: 410d07beab97e992097df42f54e1d71f9cb15b8d
--
ALTER TABLE `base_cache` CHANGE `value` `value` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;