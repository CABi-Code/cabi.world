-- Миграция: добавление привязки папки из профиля к заявке
-- Дата: 2026-02-05

ALTER TABLE `modpack_applications`
  ADD COLUMN `folder_item_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Привязанная папка из профиля пользователя'
  AFTER `contact_vk`;

ALTER TABLE `modpack_applications`
  ADD CONSTRAINT `fk_app_folder_item`
  FOREIGN KEY (`folder_item_id`) REFERENCES `user_folder_items` (`id`)
  ON DELETE SET NULL;

CREATE INDEX `idx_app_folder_item` ON `modpack_applications` (`folder_item_id`);
