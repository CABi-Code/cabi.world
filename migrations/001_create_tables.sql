-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Янв 23 2026 г., 21:16
-- Версия сервера: 10.11.14-MariaDB-0+deb12u2
-- Версия PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- База данных: `secret`
--

-- --------------------------------------------------------

--
-- Структура таблицы `application_images`
--

CREATE TABLE `application_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `auth_logs`
--

CREATE TABLE `auth_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(20) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `modpacks`
--

CREATE TABLE `modpacks` (
  `id` int(10) UNSIGNED NOT NULL,
  `platform` enum('modrinth','curseforge') NOT NULL,
  `external_id` varchar(50) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_url` varchar(500) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `downloads` bigint(20) UNSIGNED DEFAULT 0,
  `follows` int(10) UNSIGNED DEFAULT 0,
  `accepted_count` int(10) UNSIGNED DEFAULT 0,
  `external_url` varchar(500) NOT NULL,
  `cached_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `modpacks`
--
DELIMITER $$
CREATE TRIGGER `after_modpack_insert` AFTER INSERT ON `modpacks` FOR EACH ROW BEGIN
    UPDATE site_stats SET stat_value = stat_value + 1 WHERE stat_key = 'modpacks_count';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `modpack_applications`
--

CREATE TABLE `modpack_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `modpack_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `relevant_until` date DEFAULT NULL,
  `char_count` int(10) UNSIGNED DEFAULT 0,
  `contact_discord` varchar(100) DEFAULT NULL,
  `contact_telegram` varchar(100) DEFAULT NULL,
  `contact_vk` varchar(100) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `modpack_applications`
--
DELIMITER $$
CREATE TRIGGER `after_application_delete` AFTER DELETE ON `modpack_applications` FOR EACH ROW BEGIN
    UPDATE site_stats SET stat_value = stat_value - 1 WHERE stat_key = 'applications_count';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_application_insert` AFTER INSERT ON `modpack_applications` FOR EACH ROW BEGIN
    UPDATE site_stats SET stat_value = stat_value + 1 WHERE stat_key = 'applications_count';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_application_status_update` AFTER UPDATE ON `modpack_applications` FOR EACH ROW BEGIN
    IF OLD.status != 'accepted' AND NEW.status = 'accepted' THEN
        UPDATE modpacks SET accepted_count = accepted_count + 1 WHERE id = NEW.modpack_id;
    ELSEIF OLD.status = 'accepted' AND NEW.status != 'accepted' THEN
        UPDATE modpacks SET accepted_count = accepted_count - 1 WHERE id = NEW.modpack_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `site_stats`
--

CREATE TABLE `site_stats` (
  `id` int(10) UNSIGNED NOT NULL,
  `stat_key` varchar(50) NOT NULL,
  `stat_value` int(10) UNSIGNED DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `login` varchar(30) NOT NULL,
  `email` varchar(254) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `banner` varchar(255) DEFAULT NULL,
  `discord` varchar(100) DEFAULT NULL,
  `telegram` varchar(100) DEFAULT NULL,
  `vk` varchar(100) DEFAULT NULL,
  `theme` varchar(20) DEFAULT 'light',
  `view_mode` varchar(20) DEFAULT 'grid',
  `profile_bg_type` varchar(20) DEFAULT 'gradient',
  `profile_bg_value` varchar(100) DEFAULT '#2563eb,#8b5cf6',
  `avatar_bg_type` varchar(20) DEFAULT 'gradient',
  `avatar_bg_value` varchar(100) DEFAULT '#2563eb,#8b5cf6',
  `banner_bg_type` varchar(20) DEFAULT 'gradient',
  `banner_bg_value` varchar(100) DEFAULT '#2563eb,#8b5cf6',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_delete` AFTER DELETE ON `users` FOR EACH ROW BEGIN
    UPDATE site_stats SET stat_value = stat_value - 1 WHERE stat_key = 'users_count';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    UPDATE site_stats SET stat_value = stat_value + 1 WHERE stat_key = 'users_count';
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `application_images`
--
ALTER TABLE `application_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_images_app` (`application_id`);

--
-- Индексы таблицы `auth_logs`
--
ALTER TABLE `auth_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auth_logs_user` (`user_id`),
  ADD KEY `idx_auth_logs_created` (`created_at`),
  ADD KEY `idx_auth_logs_ip` (`ip_address`);

--
-- Индексы таблицы `modpacks`
--
ALTER TABLE `modpacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_modpacks_platform_external` (`platform`,`external_id`),
  ADD KEY `idx_modpacks_slug` (`slug`),
  ADD KEY `idx_modpacks_platform` (`platform`);

--
-- Индексы таблицы `modpack_applications`
--
ALTER TABLE `modpack_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applications_modpack` (`modpack_id`),
  ADD KEY `idx_applications_user` (`user_id`),
  ADD KEY `idx_applications_status` (`status`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`user_id`,`is_read`);

--
-- Индексы таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_token` (`token_hash`),
  ADD KEY `idx_password_resets_user` (`user_id`);

--
-- Индексы таблицы `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refresh_tokens_hash` (`token_hash`),
  ADD KEY `idx_refresh_tokens_user` (`user_id`),
  ADD KEY `idx_refresh_tokens_expires` (`expires_at`);

--
-- Индексы таблицы `site_stats`
--
ALTER TABLE `site_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_key` (`stat_key`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_login` (`login`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_is_active` (`is_active`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `application_images`
--
ALTER TABLE `application_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `auth_logs`
--
ALTER TABLE `auth_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `modpacks`
--
ALTER TABLE `modpacks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `modpack_applications`
--
ALTER TABLE `modpack_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `site_stats`
--
ALTER TABLE `site_stats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `application_images`
--
ALTER TABLE `application_images`
  ADD CONSTRAINT `fk_app_images_app` FOREIGN KEY (`application_id`) REFERENCES `modpack_applications` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `auth_logs`
--
ALTER TABLE `auth_logs`
  ADD CONSTRAINT `fk_auth_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `modpack_applications`
--
ALTER TABLE `modpack_applications`
  ADD CONSTRAINT `fk_applications_modpack` FOREIGN KEY (`modpack_id`) REFERENCES `modpacks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD CONSTRAINT `fk_refresh_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
