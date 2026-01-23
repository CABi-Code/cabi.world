<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class StatsRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $rows = $this->db->fetchAll('SELECT stat_key, stat_value FROM site_stats');
        $stats = [];
        foreach ($rows as $row) {
            $stats[$row['stat_key']] = (int) $row['stat_value'];
        }
        return $stats;
    }

    public function get(string $key): int
    {
        $result = $this->db->fetchOne('SELECT stat_value FROM site_stats WHERE stat_key = ?', [$key]);
        return (int) ($result['stat_value'] ?? 0);
    }

    public function increment(string $key): void
    {
        $this->db->execute('UPDATE site_stats SET stat_value = stat_value + 1 WHERE stat_key = ?', [$key]);
    }

    public function decrement(string $key): void
    {
        $this->db->execute('UPDATE site_stats SET stat_value = GREATEST(0, stat_value - 1) WHERE stat_key = ?', [$key]);
    }

    public function recalculate(): void
    {
        $this->db->execute("UPDATE site_stats SET stat_value = (SELECT COUNT(*) FROM users) WHERE stat_key = 'users_count'");
        $this->db->execute("UPDATE site_stats SET stat_value = (SELECT COUNT(*) FROM modpacks) WHERE stat_key = 'modpacks_count'");
        $this->db->execute("UPDATE site_stats SET stat_value = (SELECT COUNT(*) FROM modpack_applications) WHERE stat_key = 'applications_count'");
    }
}
