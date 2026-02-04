-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Фев 04 2026 г., 14:57
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
-- Структура таблицы `application_folder_links`
--

CREATE TABLE `application_folder_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `application_id` int(10) UNSIGNED NOT NULL,
  `folder_item_id` int(10) UNSIGNED NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Структура таблицы `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `chat_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text DEFAULT NULL,
  `likes_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_poll` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Является ли сообщение опросом',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `chat_messages`
--
DELIMITER $$
CREATE TRIGGER `after_message_delete` AFTER DELETE ON `chat_messages` FOR EACH ROW BEGIN
    UPDATE community_chats 
    SET messages_count = messages_count - 1
    WHERE id = OLD.chat_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_message_insert` AFTER INSERT ON `chat_messages` FOR EACH ROW BEGIN
    UPDATE community_chats 
    SET messages_count = messages_count + 1, last_message_at = NEW.created_at 
    WHERE id = NEW.chat_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_message_images`
--

CREATE TABLE `chat_message_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_message_likes`
--

CREATE TABLE `chat_message_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `chat_message_likes`
--
DELIMITER $$
CREATE TRIGGER `after_like_delete` AFTER DELETE ON `chat_message_likes` FOR EACH ROW BEGIN
    UPDATE chat_messages SET likes_count = likes_count - 1 WHERE id = OLD.message_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_like_insert` AFTER INSERT ON `chat_message_likes` FOR EACH ROW BEGIN
    UPDATE chat_messages SET likes_count = likes_count + 1 WHERE id = NEW.message_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_polls`
--

CREATE TABLE `chat_polls` (
  `id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED NOT NULL,
  `question` varchar(500) NOT NULL,
  `is_multiple` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Множественный выбор',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_poll_options`
--

CREATE TABLE `chat_poll_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `option_text` varchar(200) NOT NULL,
  `votes_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `chat_poll_votes`
--

CREATE TABLE `chat_poll_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `chat_poll_votes`
--
DELIMITER $$
CREATE TRIGGER `after_vote_delete` AFTER DELETE ON `chat_poll_votes` FOR EACH ROW BEGIN
    UPDATE chat_poll_options SET votes_count = votes_count - 1 WHERE id = OLD.option_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_vote_insert` AFTER INSERT ON `chat_poll_votes` FOR EACH ROW BEGIN
    UPDATE chat_poll_options SET votes_count = votes_count + 1 WHERE id = NEW.option_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `communities`
--

CREATE TABLE `communities` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subscribers_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `message_timeout` int(10) UNSIGNED DEFAULT NULL COMMENT 'Тайм-аут на сообщения в секундах (по умолчанию)',
  `files_disabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Отключить файлы по умолчанию',
  `messages_disabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Отключить сообщения по умолчанию',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `community_bans`
--

CREATE TABLE `community_bans` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `banned_by` int(10) UNSIGNED NOT NULL,
  `scope` enum('community','chat') NOT NULL DEFAULT 'community',
  `scope_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID чата (NULL для всего сообщества)',
  `reason` varchar(500) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL COMMENT 'NULL = перманентный бан',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `community_chats`
--

CREATE TABLE `community_chats` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `folder_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID папки (NULL = корень сообщества)',
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `message_timeout` int(10) UNSIGNED DEFAULT NULL COMMENT 'Переопределение тайм-аута',
  `files_disabled` tinyint(1) DEFAULT NULL COMMENT 'Переопределение настройки файлов',
  `messages_disabled` tinyint(1) DEFAULT NULL COMMENT 'Переопределение настройки сообщений',
  `messages_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `community_folders`
--

CREATE TABLE `community_folders` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID родительской папки (NULL = корень)',
  `name` varchar(100) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `message_timeout` int(10) UNSIGNED DEFAULT NULL COMMENT 'Переопределение тайм-аута',
  `files_disabled` tinyint(1) DEFAULT NULL COMMENT 'Переопределение настройки файлов',
  `messages_disabled` tinyint(1) DEFAULT NULL COMMENT 'Переопределение настройки сообщений',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `community_moderators`
--

CREATE TABLE `community_moderators` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `scope` enum('community','folder','chat') NOT NULL DEFAULT 'community',
  `scope_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID папки или чата (NULL для всего сообщества)',
  `can_delete_messages` tinyint(1) NOT NULL DEFAULT 1,
  `can_ban_users` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `community_subscribers`
--

CREATE TABLE `community_subscribers` (
  `id` int(10) UNSIGNED NOT NULL,
  `community_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Триггеры `community_subscribers`
--
DELIMITER $$
CREATE TRIGGER `after_subscriber_delete` AFTER DELETE ON `community_subscribers` FOR EACH ROW BEGIN
    UPDATE communities SET subscribers_count = subscribers_count - 1 WHERE id = OLD.community_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_subscriber_insert` AFTER INSERT ON `community_subscribers` FOR EACH ROW BEGIN
    UPDATE communities SET subscribers_count = subscribers_count + 1 WHERE id = NEW.community_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `folder_paths`
--

CREATE TABLE `folder_paths` (
  `ancestor_id` int(10) UNSIGNED NOT NULL,
  `descendant_id` int(10) UNSIGNED NOT NULL,
  `depth` int(10) UNSIGNED NOT NULL DEFAULT 0
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
-- Структура таблицы `rate_limit_blocks`
--

CREATE TABLE `rate_limit_blocks` (
  `identifier` varchar(255) NOT NULL,
  `expires_at` int(10) UNSIGNED NOT NULL,
  `captcha_solved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rate_limit_counters`
--

CREATE TABLE `rate_limit_counters` (
  `id` int(10) UNSIGNED NOT NULL,
  `key_name` varchar(255) NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rate_limit_violations`
--

CREATE TABLE `rate_limit_violations` (
  `identifier` varchar(255) NOT NULL,
  `count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `fingerprint` varchar(64) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `server_ping_history`
--

CREATE TABLE `server_ping_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `players_online` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `players_max` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `players_sample` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`players_sample`)),
  `version` varchar(100) DEFAULT NULL,
  `source` varchar(20) DEFAULT 'client',
  `is_same_as_previous` tinyint(1) NOT NULL DEFAULT 0,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp()
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
  `id` int(10) UNSIGNED NOT NULL COMMENT 'id',
  `login` varchar(16) NOT NULL COMMENT 'имя в ссылке',
  `email` varchar(254) NOT NULL COMMENT 'основная почта',
  `password_hash` varchar(255) NOT NULL COMMENT 'хэш пароль',
  `username` varchar(30) NOT NULL COMMENT 'отображаемое имя на сайте',
  `bio` text DEFAULT NULL COMMENT 'описание профиля',
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
  `subscriptions_visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'показывать вкладку подписок',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('user','premium','moderator','admin') NOT NULL DEFAULT 'user' COMMENT 'привилегия на сайте',
  `last_login_at` datetime DEFAULT NULL COMMENT 'последняя авторизация',
  `created_at` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'когда создан аккаунт',
  `subscribers_count` int(10) UNSIGNED DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `token_version` int(11) DEFAULT 1
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

-- --------------------------------------------------------

--
-- Структура таблицы `user_folder_items`
--

CREATE TABLE `user_folder_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `item_type` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `is_collapsed` tinyint(1) DEFAULT 0,
  `folder_category` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_folder_subscriptions`
--

CREATE TABLE `user_folder_subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `folder_owner_id` int(10) UNSIGNED NOT NULL,
  `subscriber_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_servers`
--

CREATE TABLE `user_servers` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `user_shortcuts`
--

CREATE TABLE `user_shortcuts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(50) DEFAULT 'link',
  `color` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;