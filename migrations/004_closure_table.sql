-- =============================================
-- Миграция v3: Closure Table для защиты от циклов
-- =============================================

-- Таблица путей (Closure Table)
CREATE TABLE IF NOT EXISTS `folder_paths` (
  `ancestor_id` int(10) UNSIGNED NOT NULL,
  `descendant_id` int(10) UNSIGNED NOT NULL,
  `depth` int(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`ancestor_id`, `descendant_id`),
  KEY `idx_fp_descendant` (`descendant_id`),
  KEY `idx_fp_depth` (`depth`),
  CONSTRAINT `fk_fp_ancestor` FOREIGN KEY (`ancestor_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fp_descendant` FOREIGN KEY (`descendant_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем поле owner_id в чаты для быстрого доступа
-- (чат теперь хранится в user_folder_items, owner = user_id)

-- Индексы для оптимизации
CREATE INDEX IF NOT EXISTS `idx_ufi_user_type` ON `user_folder_items` (`user_id`, `item_type`);
CREATE INDEX IF NOT EXISTS `idx_ufi_hidden` ON `user_folder_items` (`is_hidden`);

-- =============================================
-- Процедура для инициализации путей существующих элементов
-- Выполните после миграции если есть данные
-- =============================================
DELIMITER $$

DROP PROCEDURE IF EXISTS `init_folder_paths`$$

CREATE PROCEDURE `init_folder_paths`()
BEGIN
    -- Очищаем таблицу путей
    DELETE FROM folder_paths;
    
    -- Добавляем самоссылки (каждый элемент - потомок самого себя с depth=0)
    INSERT INTO folder_paths (ancestor_id, descendant_id, depth)
    SELECT id, id, 0 FROM user_folder_items;
    
    -- Добавляем прямые связи родитель-потомок (depth=1)
    INSERT INTO folder_paths (ancestor_id, descendant_id, depth)
    SELECT parent_id, id, 1 
    FROM user_folder_items 
    WHERE parent_id IS NOT NULL;
    
    -- Добавляем транзитивные связи (до 10 уровней вложенности)
    -- Level 2
    INSERT IGNORE INTO folder_paths (ancestor_id, descendant_id, depth)
    SELECT p1.ancestor_id, p2.descendant_id, p1.depth + p2.depth
    FROM folder_paths p1
    JOIN folder_paths p2 ON p1.descendant_id = p2.ancestor_id
    WHERE p1.depth > 0 AND p2.depth > 0;
    
    -- Level 3-4
    INSERT IGNORE INTO folder_paths (ancestor_id, descendant_id, depth)
    SELECT p1.ancestor_id, p2.descendant_id, p1.depth + p2.depth
    FROM folder_paths p1
    JOIN folder_paths p2 ON p1.descendant_id = p2.ancestor_id
    WHERE p1.depth > 0 AND p2.depth > 0;
    
END$$

DELIMITER ;

-- Вызов процедуры (раскомментируйте если нужно инициализировать)
-- CALL init_folder_paths();
