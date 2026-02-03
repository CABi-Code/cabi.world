<?php

namespace App\Repository\ApplicationRepository;

trait ImageTrait {

    public function addImage(int $applicationId, string $path, int $sortOrder = 0): int
    {
        // Проверяем лимит изображений
        $currentCount = $this->countImages($applicationId);
        if ($currentCount >= self::MAX_IMAGES) {
            return 0;
        }
        
        $this->db->execute(
            'INSERT INTO application_images (application_id, image_path, sort_order) VALUES (?, ?, ?)', 
            [$applicationId, $path, $sortOrder]
        );
        return $this->db->lastInsertId();
    }

    public function countImages(int $applicationId): int
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM application_images WHERE application_id = ?',
            [$applicationId]
        );
        return (int) ($result['cnt'] ?? 0);
    }

    public function getImages(int $applicationId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM application_images WHERE application_id = ? ORDER BY sort_order', 
            [$applicationId]
        );
    }

    public function deleteImage(int $imageId, int $userId): bool
    {
        return $this->db->execute(
            'DELETE ai FROM application_images ai 
             JOIN modpack_applications a ON ai.application_id = a.id 
             WHERE ai.id = ? AND a.user_id = ?',
            [$imageId, $userId]
        ) > 0;
    }

    public function deleteAllImages(int $applicationId): void
    {
        $this->db->execute('DELETE FROM application_images WHERE application_id = ?', [$applicationId]);
    }
}

?>