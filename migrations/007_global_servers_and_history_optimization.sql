-- Migration 007: Global servers table + history optimization
-- Servers are now stored globally (one instance per ip:port).
-- user_folder_items of type 'server' reference global_servers via server_id in settings.
-- server_ping_history stores NULLs for unchanged fields (is_same_as_previous=1).

-- --------------------------------------------------------
-- Global servers table: one record per unique ip:port
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `global_servers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `port` int(5) UNSIGNED NOT NULL DEFAULT 25565,
  `name` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `players_online` int(10) UNSIGNED DEFAULT 0,
  `players_max` int(10) UNSIGNED DEFAULT 0,
  `players_sample` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `motd_raw` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `motd_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `favicon` longtext DEFAULT NULL,
  `last_check` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_address_port` (`address`, `port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Add server_id column to server_ping_history to reference global_servers
-- --------------------------------------------------------
ALTER TABLE `server_ping_history`
  ADD COLUMN `server_id` int(10) UNSIGNED DEFAULT NULL AFTER `item_id`,
  ADD INDEX `idx_server_id` (`server_id`),
  ADD INDEX `idx_server_checked` (`server_id`, `checked_at`);

-- --------------------------------------------------------
-- Optimize history: allow NULLs for fields when is_same_as_previous=1
-- (These columns are already nullable, but let's make sure)
-- --------------------------------------------------------
ALTER TABLE `server_ping_history`
  MODIFY COLUMN `players_online` int(10) UNSIGNED DEFAULT NULL,
  MODIFY COLUMN `players_max` int(10) UNSIGNED DEFAULT NULL,
  MODIFY COLUMN `players_sample` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  MODIFY COLUMN `version` varchar(50) DEFAULT NULL;

-- --------------------------------------------------------
-- Migrate existing servers from user_folder_items settings to global_servers
-- This creates global_server entries and links them
-- --------------------------------------------------------
-- (Run this as a PHP migration script, not raw SQL, since we need JSON parsing)
