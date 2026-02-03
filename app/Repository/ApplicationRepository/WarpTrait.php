<?php

namespace App\Repository\ApplicationRepository;

use App\Core\DbFields;

trait WarpTrait {

    /**
     * Создание заявки
     * Если контакты null - будут использоваться контакты из профиля
     */
    public function create(
        int $modpackId, 
        int $userId, 
        string $message, 
        ?string $discord, 
        ?string $telegram, 
        ?string $vk, 
        ?string $relevantUntil = null
    ): int {
        $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        
        $dateValidation = $this->validateRelevantUntil($relevantUntil);
        if (!$dateValidation['valid']) {
            throw new \InvalidArgumentException($dateValidation['error']);
        }
        
        $this->db->execute(
            'INSERT INTO modpack_applications 
             (modpack_id, user_id, message, char_count, relevant_until, 
              contact_discord, contact_telegram, contact_vk, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")',
            [
                $modpackId, 
                $userId, 
                $message, 
                mb_strlen($message), 
                $dateValidation['date'], 
                $discord, 
                $telegram, 
                $vk
            ]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Обновление заявки
     */
    public function update(
        int $id, 
        int $userId, 
        string $message, 
        ?string $discord, 
        ?string $telegram, 
        ?string $vk, 
        ?string $relevantUntil = null
    ): bool {
        $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        
        $dateValidation = $this->validateRelevantUntil($relevantUntil);
        if (!$dateValidation['valid']) {
            throw new \InvalidArgumentException($dateValidation['error']);
        }
        
        return $this->db->execute(
            'UPDATE modpack_applications 
             SET message = ?, char_count = ?, relevant_until = ?, 
                 contact_discord = ?, contact_telegram = ?, contact_vk = ?, 
                 status = "pending", updated_at = NOW() 
             WHERE id = ? AND user_id = ?',
            [
                $message, 
                mb_strlen($message), 
                $dateValidation['date'], 
                $discord, 
                $telegram, 
                $vk, 
                $id, 
                $userId
            ]
        ) > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM modpack_applications WHERE id = ? AND user_id = ?', 
            [$id, $userId]
        ) > 0;
    }

    public function toggleHidden(int $id, int $userId): bool
    {
        return $this->db->execute(
            'UPDATE modpack_applications SET is_hidden = NOT is_hidden WHERE id = ? AND user_id = ?', 
            [$id, $userId]
        ) > 0;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::APP_FULL . '
             FROM modpack_applications a
             JOIN modpacks m ON a.modpack_id = m.id
             JOIN users u ON a.user_id = u.id
             WHERE a.id = ?',
            [$id]
        );
    }
}

?>