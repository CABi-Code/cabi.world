-- Добавление роли пользователя
ALTER TABLE `users` 
ADD COLUMN `role` ENUM('user', 'premium', 'moderator', 'admin') NOT NULL DEFAULT 'user' AFTER `is_active`;

-- Индекс для быстрого поиска по роли
CREATE INDEX `idx_users_role` ON `users` (`role`);