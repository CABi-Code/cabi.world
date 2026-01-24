<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class ModpackRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT ' . DbFields::MODPACK_BASE . ' FROM modpacks WHERE id = ?', [$id]);
    }

    public function findBySlug(string $platform, string $slug): ?array
    {
        return $this->db->fetchOne('SELECT ' . DbFields::MODPACK_BASE . ' FROM modpacks WHERE platform = ? AND slug = ?', [$platform, $slug]);
    }

    public function findByExternalId(string $platform, string $externalId): ?array
    {
        return $this->db->fetchOne('SELECT ' . DbFields::MODPACK_BASE . ' FROM modpacks WHERE platform = ? AND external_id = ?', [$platform, $externalId]);
    }

    public function getOrCreate(string $platform, array $data): array
    {
        $existing = $this->findBySlug($platform, $data['slug']);
        if ($existing) {
            $this->update($existing['id'], $data);
            return array_merge($existing, $data);
        }

        $this->db->execute(
            'INSERT INTO modpacks (platform, external_id, slug, name, description, icon_url, author, downloads, follows, external_url, cached_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$platform, $data['external_id'], $data['slug'], $data['name'], $data['description'] ?? '', 
             $data['icon_url'] ?? null, $data['author'] ?? 'Unknown', $data['downloads'] ?? 0, 
             $data['follows'] ?? 0, $data['external_url'] ?? '']
        );

        return $this->findById($this->db->lastInsertId());
    }

    public function update(int $id, array $data): void
    {
        $allowed = ['name', 'description', 'icon_url', 'author', 'downloads', 'follows', 'external_url'];
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        
        if (!empty($sets)) {
            $sets[] = 'cached_at = NOW()';
            $params[] = $id;
            $this->db->execute('UPDATE modpacks SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
        }
    }

    public function exists(string $platform, string $slug): bool
    {
        return (bool) $this->db->fetchOne('SELECT 1 FROM modpacks WHERE platform = ? AND slug = ?', [$platform, $slug]);
    }

    /**
     * Модпаки с активными заявками (только одобренные, не скрытые)
     * Проверяем реальное количество активных заявок, а не кэшированное значение
     */
    public function getPopularWithApplications(string $platform, int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT m.id, m.platform, m.external_id, m.slug, m.name, m.description, 
                    m.icon_url, m.author, m.downloads, m.follows, m.external_url, m.cached_at,
                    COUNT(a.id) as active_app_count
             FROM modpacks m
             INNER JOIN modpack_applications a ON m.id = a.modpack_id
             WHERE m.platform = ? 
               AND a.status = "accepted" 
               AND a.is_hidden = 0
             GROUP BY m.id
             HAVING active_app_count > 0
             ORDER BY active_app_count DESC 
             LIMIT ?',
            [$platform, $limit]
        );
    }

    /**
     * Получить количество активных заявок для списка модпаков
     * (только одобренные и не скрытые)
     */
    public function getApplicationCounts(array $slugs, string $platform): array
    {
        if (empty($slugs)) return [];
        
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $params = array_merge([$platform], $slugs);
        
        $rows = $this->db->fetchAll(
            "SELECT m.slug, COUNT(a.id) as app_count 
             FROM modpacks m
             LEFT JOIN modpack_applications a ON m.id = a.modpack_id 
                AND a.status = 'accepted' AND a.is_hidden = 0
             WHERE m.platform = ? AND m.slug IN ({$placeholders})
             GROUP BY m.id, m.slug",
            $params
        );
        
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['app_count'];
        }
        return $counts;
    }

    public function incrementAcceptedCount(int $id): void
    {
        $this->db->execute('UPDATE modpacks SET accepted_count = accepted_count + 1 WHERE id = ?', [$id]);
    }

    public function decrementAcceptedCount(int $id): void
    {
        $this->db->execute('UPDATE modpacks SET accepted_count = GREATEST(0, accepted_count - 1) WHERE id = ?', [$id]);
    }

    /**
     * Пересчитать accepted_count для модпака на основе реальных данных
     */
    public function recalculateAcceptedCount(int $id): void
    {
        $this->db->execute(
            'UPDATE modpacks m SET accepted_count = (
                SELECT COUNT(*) FROM modpack_applications a 
                WHERE a.modpack_id = m.id AND a.status = "accepted" AND a.is_hidden = 0
            ) WHERE m.id = ?',
            [$id]
        );
    }
}