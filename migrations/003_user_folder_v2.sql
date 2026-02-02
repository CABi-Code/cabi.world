-- =============================================
-- Миграция: Переработка системы "Моя папка"
-- Заменяет community_* таблицы
-- =============================================

-- Удаляем старые таблицы (если нужно)
-- DROP TABLE IF EXISTS community_subscriptions;
-- DROP TABLE IF EXISTS community_bans;
-- DROP TABLE IF EXISTS community_moderators;
-- DROP TABLE IF EXISTS community_chats;
-- DROP TABLE IF EXISTS community_folders;
-- DROP TABLE IF EXISTS communities;

-- =============================================
-- Основная таблица структуры папки пользователя
-- Хранит ВСЕ: папки, чаты, модпаки, серверы, заявки, ярлыки
-- =============================================
CREATE TABLE `user_folder_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  
  -- Тип: folder, chat, modpack, mod, server, application, shortcut
  `item_type` varchar(20) NOT NULL,
  
  -- Основные поля
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT 0,
  `is_collapsed` tinyint(1) DEFAULT 0,
  
  -- Категория папки (для специальных папок)
  -- main_applications - главные заявки (видны на сайте)
  -- modpacks - модпаки с заявками
  -- servers - серверы
  -- NULL - обычная папка
  `folder_category` varchar(50) DEFAULT NULL,
  
  -- Ссылка на внешний объект (заявка, модпак и т.д.)
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  
  -- Для элементов-ярлыков на заявки
  `is_hidden` tinyint(1) DEFAULT 0,
  
  -- JSON настройки (для чатов: timeout, files_disabled и т.д.)
  `settings` JSON DEFAULT NULL,
  
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_ufi_user` (`user_id`),
  KEY `idx_ufi_parent` (`parent_id`),
  KEY `idx_ufi_type` (`item_type`),
  KEY `idx_ufi_category` (`folder_category`),
  KEY `idx_ufi_reference` (`reference_type`, `reference_id`),
  KEY `idx_ufi_sort` (`user_id`, `parent_id`, `sort_order`),
  
  CONSTRAINT `fk_ufi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ufi_parent` FOREIGN KEY (`parent_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Таблица подписок (на папки пользователей)
-- =============================================
CREATE TABLE `user_folder_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `folder_owner_id` int(10) UNSIGNED NOT NULL,
  `subscriber_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ufs_subscription` (`folder_owner_id`, `subscriber_id`),
  KEY `idx_ufs_subscriber` (`subscriber_id`),
  
  CONSTRAINT `fk_ufs_owner` FOREIGN KEY (`folder_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ufs_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Таблица серверов
-- =============================================
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
  KEY `idx_us_user` (`user_id`),
  
  CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Таблица ярлыков (внешние ссылки)
-- =============================================
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
  KEY `idx_ush_user` (`user_id`),
  
  CONSTRAINT `fk_ush_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Связь заявок с папкой (ярлыки заявок в модпаках)
-- =============================================
CREATE TABLE `application_folder_links` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` int(10) UNSIGNED NOT NULL,
  `folder_item_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_afl_link` (`application_id`, `folder_item_id`),
  KEY `idx_afl_folder` (`folder_item_id`),
  
  CONSTRAINT `fk_afl_app` FOREIGN KEY (`application_id`) REFERENCES `modpack_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_afl_folder` FOREIGN KEY (`folder_item_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Добавляем поле для подсчёта подписчиков в users
-- =============================================
ALTER TABLE `users` ADD COLUMN `subscribers_count` int(10) UNSIGNED DEFAULT 0 AFTER `created_at`;

-- =============================================
-- Триггеры для подсчёта подписчиков
-- =============================================
DELIMITER $$

CREATE TRIGGER `after_subscription_insert` AFTER INSERT ON `user_folder_subscriptions` FOR EACH ROW
BEGIN
    UPDATE users SET subscribers_count = subscribers_count + 1 WHERE id = NEW.folder_owner_id;
END$$

CREATE TRIGGER `after_subscription_delete` AFTER DELETE ON `user_folder_subscriptions` FOR EACH ROW
BEGIN
    UPDATE users SET subscribers_count = GREATEST(0, subscribers_count - 1) WHERE id = OLD.folder_owner_id;
END$$

DELIMITER ;

-- =============================================
-- Индекс для быстрого поиска видимых заявок
-- Заявка видна, если:
-- 1. Есть ярлык в папке модпака внутри папки main_applications
-- 2. Заявка одобрена (status = 'accepted')
-- =============================================
CREATE INDEX `idx_app_visible` ON `modpack_applications` (`status`, `is_hidden`);
