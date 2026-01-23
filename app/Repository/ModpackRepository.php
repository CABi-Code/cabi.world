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
     * Модпаки с активными заявками (только актуальные)
     */
    public function getPopularWithApplications(string $platform, int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT ' . DbFields::MODPACK_BASE . ' 
             FROM modpacks 
             WHERE platform = ? AND accepted_count > 0 
             ORDER BY accepted_count DESC 
             LIMIT ?',
            [$platform, $limit]
        );
    }

    /**
     * Получить количество заявок для списка модпаков
     */
    public function getApplicationCounts(array $slugs, string $platform): array
    {
        if (empty($slugs)) return [];
        
        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $params = array_merge([$platform], $slugs);
        
        $rows = $this->db->fetchAll(
            "SELECT slug, accepted_count FROM modpacks WHERE platform = ? AND slug IN ({$placeholders})",
            $params
        );
        
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['slug']] = (int) $row['accepted_count'];
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
}
