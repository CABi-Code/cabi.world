-- =============================================
-- Миграция: История пинга серверов
-- =============================================

CREATE TABLE IF NOT EXISTS `server_ping_history` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` int(10) UNSIGNED NOT NULL,
  
  -- Статус
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `players_online` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `players_max` int(10) UNSIGNED NOT NULL DEFAULT 0,
  
  -- Список игроков (JSON)
  `players_sample` JSON DEFAULT NULL,
  
  -- Версия сервера
  `version` varchar(100) DEFAULT NULL,
  
  -- Источник данных: 'client' или 'server'
  `source` varchar(20) DEFAULT 'client',
  
  -- Флаг: данные те же, что и в предыдущей записи
  -- Если true - не нужно читать players_sample и version, они такие же
  `is_same_as_previous` tinyint(1) NOT NULL DEFAULT 0,
  
  -- Время проверки
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_sph_item` (`item_id`),
  KEY `idx_sph_time` (`checked_at`),
  KEY `idx_sph_item_time` (`item_id`, `checked_at`),
  
  CONSTRAINT `fk_sph_item` FOREIGN KEY (`item_id`) 
    REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индекс для быстрой очистки старых записей
CREATE INDEX `idx_sph_cleanup` ON `server_ping_history` (`checked_at`);
