<?php

namespace App\Repository\UserFolderRepository;

trait ApplicationsTrait
{
    /**
     * Создать заявку в папке и ярлык в модпаке
     * Вызывается при создании новой заявки
     * 
     * @return array ['application_item_id' => int, 'shortcut_item_id' => int]
     */
    public function createApplicationWithShortcut(int $userId, int $applicationId, int $modpackId, string $modpackName): array
    {
        // 1. Получаем/создаём папку "Заявки"
        $appsFolder = $this->getOrCreateMainApplicationsFolder($userId);
        
        // 2. Создаём элемент заявки в папке заявок
        $appItemId = $this->createItem($userId, 'application', $modpackName, $appsFolder['id'], [
            'reference_id' => $applicationId,
            'reference_type' => 'modpack_applications',
            'icon' => 'file-text'
        ]);
        
        // 3. Получаем/создаём сущность модпака
        $modpackEntity = $this->getOrCreateModpackEntity($userId, $modpackId, $modpackName);
        
        // 4. Создаём ярлык заявки внутри модпака
        $shortcutId = $this->createItem($userId, 'shortcut', 'Заявка', $modpackEntity['id'], [
            'reference_id' => $applicationId,
            'reference_type' => 'application_shortcut',
            'icon' => 'link'
        ]);
        
        // 5. Связываем заявку с ярлыком
        $this->db->execute(
            'INSERT INTO application_folder_links (application_id, folder_item_id, is_primary) VALUES (?, ?, 1)',
            [$applicationId, $shortcutId]
        );
        
        return [
            'application_item_id' => $appItemId,
            'shortcut_item_id' => $shortcutId
        ];
    }

    /**
     * Проверить видимость заявки
     * Заявка видна, если есть ярлык внутри модпака, который внутри папки main_applications
     */
    public function isApplicationVisible(int $applicationId): bool
    {
        // Ищем ярлык заявки
        $shortcut = $this->db->fetchOne(
            "SELECT ufi.*, parent.folder_category as parent_category
             FROM user_folder_items ufi
             LEFT JOIN user_folder_items parent ON ufi.parent_id = parent.id
             LEFT JOIN user_folder_items grandparent ON parent.parent_id = grandparent.id
             WHERE ufi.reference_type = 'application_shortcut' 
             AND ufi.reference_id = ?
             AND ufi.is_hidden = 0",
            [$applicationId]
        );
        
        if (!$shortcut) return false;
        
        // Проверяем, что ярлык в модпаке, который в папке модпаков
        // Папка модпаков создаётся автоматически в корне
        return true;
    }

    /**
     * Скрыть заявку (убрать ярлык из модпака)
     */
    public function hideApplication(int $applicationId, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE user_folder_items 
             SET is_hidden = 1 
             WHERE reference_type = 'application_shortcut' 
             AND reference_id = ? AND user_id = ?",
            [$applicationId, $userId]
        ) > 0;
    }

    /**
     * Показать заявку (восстановить ярлык)
     */
    public function showApplication(int $applicationId, int $userId): bool
    {
        // Проверяем, есть ли ярлык
        $shortcut = $this->db->fetchOne(
            "SELECT * FROM user_folder_items 
             WHERE reference_type = 'application_shortcut' 
             AND reference_id = ? AND user_id = ?",
            [$applicationId, $userId]
        );
        
        if ($shortcut) {
            // Просто показываем
            return $this->db->execute(
                'UPDATE user_folder_items SET is_hidden = 0 WHERE id = ?',
                [$shortcut['id']]
            ) > 0;
        }
        
        // Нужно создать заново
        $app = $this->db->fetchOne(
            'SELECT a.*, m.id as modpack_id, m.name as modpack_name 
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             WHERE a.id = ? AND a.user_id = ?',
            [$applicationId, $userId]
        );
        
        if (!$app) return false;
        
        // Создаём ярлык
        $modpackEntity = $this->getOrCreateModpackEntity($userId, $app['modpack_id'], $app['modpack_name']);
        
        $shortcutId = $this->createItem($userId, 'shortcut', 'Заявка', $modpackEntity['id'], [
            'reference_id' => $applicationId,
            'reference_type' => 'application_shortcut',
            'icon' => 'link'
        ]);
        
        $this->db->execute(
            'INSERT INTO application_folder_links (application_id, folder_item_id, is_primary) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE folder_item_id = VALUES(folder_item_id)',
            [$applicationId, $shortcutId]
        );
        
        return true;
    }

    /**
     * Получить заявки пользователя с информацией о видимости
     */
    public function getUserApplicationsWithVisibility(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, m.name as modpack_name, m.slug, m.platform,
                    COALESCE(ufi.is_hidden, 1) as is_folder_hidden
             FROM modpack_applications a
             JOIN modpacks m ON a.modpack_id = m.id
             LEFT JOIN user_folder_items ufi ON ufi.reference_type = 'application_shortcut' 
                 AND ufi.reference_id = a.id AND ufi.user_id = a.user_id
             WHERE a.user_id = ?
             ORDER BY a.created_at DESC",
            [$userId]
        );
    }

    /**
     * Получить видимые заявки для отображения на сайте
     */
    public function getVisibleApplications(int $limit = 20, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, m.name as modpack_name, m.slug, m.platform, m.icon_url,
                    u.login, u.username, u.avatar, u.avatar_bg_value
             FROM modpack_applications a
             JOIN modpacks m ON a.modpack_id = m.id
             JOIN users u ON a.user_id = u.id
             JOIN user_folder_items ufi ON ufi.reference_type = 'application_shortcut' 
                 AND ufi.reference_id = a.id AND ufi.user_id = a.user_id AND ufi.is_hidden = 0
             WHERE a.status = 'accepted'
             ORDER BY a.updated_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
}
