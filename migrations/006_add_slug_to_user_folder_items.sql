-- Добавление поля slug для UUID-hash ссылок на элементы
-- slug хранит уникальную строку для URL вида /@user/folder-my-slug

ALTER TABLE `user_folder_items`
ADD COLUMN `slug` varchar(100) DEFAULT NULL AFTER `name`;

-- Генерация slug для существующих записей (id + created_at хэш)
UPDATE `user_folder_items`
SET `slug` = LOWER(CONCAT(
    SUBSTRING(MD5(CONCAT(id, '-', created_at, '-', item_type, '-', COALESCE(name, ''))), 1, 8)
))
WHERE `slug` IS NULL;

-- Уникальный индекс
ALTER TABLE `user_folder_items` ADD UNIQUE INDEX `idx_slug` (`slug`);

-- slug не должен быть NULL для новых записей
ALTER TABLE `user_folder_items` MODIFY COLUMN `slug` varchar(100) NOT NULL;
