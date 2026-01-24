<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class ApplicationRepository
{
    private Database $db;
    public const MAX_MESSAGE_LENGTH = 2000;
    public const MAX_RELEVANCE_DAYS = 31; // Максимум 1 месяц

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Валидация даты актуальности (обязательное поле, не больше 1 месяца вперёд)
     * 
     * @param string|null $date Дата в формате Y-m-d
     * @return array ['valid' => bool, 'date' => string|null, 'error' => string|null]
     */
    public function validateRelevantUntil(?string $date): array
    {
        // Дата обязательна
        if (empty($date)) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата актуальности обязательна'
            ];
        }
        
        // Проверка формата даты (Y-m-d)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Неверный формат даты. Используйте формат ГГГГ-ММ-ДД'
            ];
        }
        
        // Парсинг даты
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Некорректная дата'
            ];
        }
        
        // Проверка что дата валидна (не 2024-02-31 и т.п.)
        $parsedDate = date('Y-m-d', $timestamp);
        if ($parsedDate !== $date) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Некорректная дата'
            ];
        }
        
        $maxTimestamp = strtotime('+' . self::MAX_RELEVANCE_DAYS . ' days');
        $minTimestamp = strtotime('today');
        
        // Дата не может быть в прошлом
        if ($timestamp < $minTimestamp) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата не может быть в прошлом'
            ];
        }
        
        // Дата не может быть больше чем через месяц
        if ($timestamp > $maxTimestamp) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата не может быть больше чем через ' . self::MAX_RELEVANCE_DAYS . ' дней'
            ];
        }
        
        return [
            'valid' => true,
            'date' => $parsedDate,
            'error' => null
        ];
    }

    /**
     * Создание заявки
     * 
     * @throws \InvalidArgumentException если дата невалидна
     */
    public function create(int $modpackId, int $userId, string $message, ?string $discord, ?string $telegram, ?string $vk, ?string $relevantUntil = null): int
    {
        $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        
        // Валидация даты
        $dateValidation = $this->validateRelevantUntil($relevantUntil);
        if (!$dateValidation['valid']) {
            throw new \InvalidArgumentException($dateValidation['error']);
        }
        
        $this->db->execute(
            'INSERT INTO modpack_applications (modpack_id, user_id, message, char_count, relevant_until, contact_discord, contact_telegram, contact_vk, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")',
            [$modpackId, $userId, $message, mb_strlen($message), $dateValidation['date'], $discord, $telegram, $vk]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Обновление заявки
     * 
     * @throws \InvalidArgumentException если дата невалидна
     */
    public function update(int $id, int $userId, string $message, ?string $discord, ?string $telegram, ?string $vk, ?string $relevantUntil = null): bool
    {
        $message = mb_substr($message, 0, self::MAX_MESSAGE_LENGTH);
        
        // Валидация даты
        $dateValidation = $this->validateRelevantUntil($relevantUntil);
        if (!$dateValidation['valid']) {
            throw new \InvalidArgumentException($dateValidation['error']);
        }
        
        return $this->db->execute(
            'UPDATE modpack_applications 
             SET message = ?, char_count = ?, relevant_until = ?, contact_discord = ?, contact_telegram = ?, contact_vk = ?, status = "pending", updated_at = NOW() 
             WHERE id = ? AND user_id = ?',
            [$message, mb_strlen($message), $dateValidation['date'], $discord, $telegram, $vk, $id, $userId]
        ) > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->execute('DELETE FROM modpack_applications WHERE id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    public function toggleHidden(int $id, int $userId): bool
    {
        return $this->db->execute('UPDATE modpack_applications SET is_hidden = NOT is_hidden WHERE id = ? AND user_id = ?', [$id, $userId]) > 0;
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

    /**
     * Все одобренные заявки с сортировкой
     * По умолчанию: сначала актуальные по дате актуальности, потом неактуальные по дате создания
     */
    public function findAllAccepted(int $limit = 50, int $offset = 0, string $sort = 'relevance'): array
    {
        $orderBy = match($sort) {
            'date' => 'a.created_at DESC',
            'popular' => 'm.accepted_count DESC, a.created_at DESC',
            default => 'CASE 
                WHEN a.relevant_until IS NULL THEN 2
                WHEN a.relevant_until >= CURDATE() THEN 1 
                ELSE 3 
            END ASC,
            CASE 
                WHEN a.relevant_until >= CURDATE() THEN a.relevant_until 
                ELSE NULL 
            END ASC,
            a.created_at DESC'
        };

        return $this->db->fetchAll(
            "SELECT " . DbFields::APP_FULL . "
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             JOIN users u ON a.user_id = u.id
             WHERE a.status = 'accepted' AND a.is_hidden = 0
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Только актуальные заявки (для страниц модпаков)
     */
    public function findActiveAccepted(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT " . DbFields::APP_FULL . "
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             JOIN users u ON a.user_id = u.id
             WHERE a.status = 'accepted' AND a.is_hidden = 0 
               AND (a.relevant_until IS NULL OR a.relevant_until >= CURDATE())
             ORDER BY a.relevant_until ASC, a.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    public function countAllAccepted(): int
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM modpack_applications WHERE status = 'accepted' AND is_hidden = 0");
        return (int) ($result['cnt'] ?? 0);
    }

    public function countActiveAccepted(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM modpack_applications 
             WHERE status = 'accepted' AND is_hidden = 0 
               AND (relevant_until IS NULL OR relevant_until >= CURDATE())"
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function findByModpack(int $modpackId, ?int $currentUserId = null, int $limit = 50, int $offset = 0): array
    {
        if ($currentUserId) {
            return $this->db->fetchAll(
                'SELECT ' . DbFields::APP_WITH_USER . '
                 FROM modpack_applications a 
                 JOIN users u ON a.user_id = u.id 
                 WHERE a.modpack_id = ? AND (a.status = "accepted" OR a.user_id = ?)
                 ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
                [$modpackId, $currentUserId, $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            'SELECT ' . DbFields::APP_WITH_USER . '
             FROM modpack_applications a 
             JOIN users u ON a.user_id = u.id 
             WHERE a.modpack_id = ? AND a.status = "accepted"
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            [$modpackId, $limit, $offset]
        );
    }

    public function findByUser(int $userId, bool $isOwner = false, int $limit = 50, int $offset = 0): array
    {
        if ($isOwner) {
            return $this->db->fetchAll(
                'SELECT ' . DbFields::APP_WITH_MODPACK . '
                 FROM modpack_applications a JOIN modpacks m ON a.modpack_id = m.id 
                 WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            );
        }
        return $this->db->fetchAll(
            'SELECT ' . DbFields::APP_WITH_MODPACK . '
             FROM modpack_applications a JOIN modpacks m ON a.modpack_id = m.id 
             WHERE a.user_id = ? AND a.status = "accepted" AND a.is_hidden = 0
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    public function countByModpack(int $modpackId, bool $acceptedOnly = true): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM modpack_applications WHERE modpack_id = ?';
        if ($acceptedOnly) $sql .= ' AND status = "accepted"';
        $result = $this->db->fetchOne($sql, [$modpackId]);
        return (int) ($result['cnt'] ?? 0);
    }

    public function userHasApplied(int $modpackId, int $userId): bool
    {
        return (bool) $this->db->fetchOne('SELECT 1 FROM modpack_applications WHERE modpack_id = ? AND user_id = ?', [$modpackId, $userId]);
    }

    public function getUserApplication(int $modpackId, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::APP_BASE . ' FROM modpack_applications a WHERE a.modpack_id = ? AND a.user_id = ?', 
            [$modpackId, $userId]
        );
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) return false;
        return $this->db->execute('UPDATE modpack_applications SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]) > 0;
    }

    /**
     * Установить статус заявки (для модераторов)
     */
    public function setStatusByModerator(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) return false;
        return $this->db->execute('UPDATE modpack_applications SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]) > 0;
    }

    /**
     * Получить все заявки на рассмотрении (для панели управления)
     */
    public function findPending(int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            'SELECT ' . DbFields::APP_FULL . '
             FROM modpack_applications a 
             JOIN modpacks m ON a.modpack_id = m.id 
             JOIN users u ON a.user_id = u.id
             WHERE a.status = "pending"
             ORDER BY a.created_at ASC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Подсчёт заявок на рассмотрении
     */
    public function countPending(): int
    {
        $result = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM modpack_applications WHERE status = 'pending'");
        return (int) ($result['cnt'] ?? 0);
    }

    /**
     * Получить все заявки с фильтрацией (для панели управления)
     */
    public function findAllForAdmin(int $limit = 50, int $offset = 0, ?string $status = null): array
    {
        $sql = 'SELECT ' . DbFields::APP_FULL . '
                FROM modpack_applications a 
                JOIN modpacks m ON a.modpack_id = m.id 
                JOIN users u ON a.user_id = u.id';
        
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE a.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Подсчёт всех заявок с фильтрацией
     */
    public function countAllForAdmin(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) as cnt FROM modpack_applications';
        $params = [];
        
        if ($status) {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int) ($result['cnt'] ?? 0);
    }

    public function addImage(int $applicationId, string $path, int $sortOrder = 0): int
    {
        $this->db->execute('INSERT INTO application_images (application_id, image_path, sort_order) VALUES (?, ?, ?)', [$applicationId, $path, $sortOrder]);
        return $this->db->lastInsertId();
    }

    public function getImages(int $applicationId): array
    {
        return $this->db->fetchAll('SELECT * FROM application_images WHERE application_id = ? ORDER BY sort_order', [$applicationId]);
    }

    public function deleteImage(int $imageId, int $userId): bool
    {
        return $this->db->execute(
            'DELETE ai FROM application_images ai JOIN modpack_applications a ON ai.application_id = a.id WHERE ai.id = ? AND a.user_id = ?',
            [$imageId, $userId]
        ) > 0;
    }

    public function deleteAllImages(int $applicationId): void
    {
        $this->db->execute('DELETE FROM application_images WHERE application_id = ?', [$applicationId]);
    }
}