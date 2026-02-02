-- =============================================
-- Миграция: Таблицы для "Моей папки" пользователя
-- =============================================

-- Основная таблица элементов папки пользователя
-- Хранит ВСЕ элементы: сущности (категории, модпаки, моды) и элементы (серверы, заявки, чаты, ярлыки)
CREATE TABLE `user_folder_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `item_type` enum('category','modpack','mod','server','application','chat','shortcut') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `is_collapsed` tinyint(1) DEFAULT 0,
  `settings` JSON DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_folder_user` (`user_id`),
  KEY `idx_user_folder_parent` (`parent_id`),
  KEY `idx_user_folder_type` (`item_type`),
  KEY `idx_user_folder_sort` (`sort_order`),
  CONSTRAINT `fk_user_folder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_folder_parent` FOREIGN KEY (`parent_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица серверов (для элемента "сервер")
CREATE TABLE `user_servers` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `port` int(5) UNSIGNED DEFAULT 25565,
  `version` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon_url` varchar(500) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `players_online` int(10) UNSIGNED DEFAULT 0,
  `players_max` int(10) UNSIGNED DEFAULT 0,
  `last_check` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_servers_user` (`user_id`),
  CONSTRAINT `fk_user_servers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица ярлыков (для элемента "ярлык")
CREATE TABLE `user_shortcuts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(50) DEFAULT 'link',
  `color` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_shortcuts_user` (`user_id`),
  CONSTRAINT `fk_user_shortcuts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаём папку "Заявки" по умолчанию при создании пользователя
-- (это будет делаться через триггер или код)
