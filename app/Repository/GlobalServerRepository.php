<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class GlobalServerRepository
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Найти или создать глобальный сервер по адресу и порту
     */
    public function findOrCreate(string $address, int $port = 25565): array
    {
        $address = strtolower(trim($address));

        $server = $this->db->fetchOne(
            'SELECT * FROM global_servers WHERE address = ? AND port = ?',
            [$address, $port]
        );

        if ($server) {
            return $server;
        }

        $this->db->execute(
            'INSERT INTO global_servers (address, port) VALUES (?, ?)',
            [$address, $port]
        );

        return $this->db->fetchOne(
            'SELECT * FROM global_servers WHERE id = ?',
            [$this->db->lastInsertId()]
        );
    }

    /**
     * Получить сервер по ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM global_servers WHERE id = ?', [$id]);
    }

    /**
     * Получить сервер по адресу и порту
     */
    public function getByAddress(string $address, int $port = 25565): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM global_servers WHERE address = ? AND port = ?',
            [strtolower(trim($address)), $port]
        );
    }

    /**
     * Обновить статус глобального сервера после пинга
     */
    public function updateStatus(int $serverId, array $data): void
    {
        $this->db->execute(
            'UPDATE global_servers SET
                is_online = ?, players_online = ?, players_max = ?,
                players_sample = ?, version = ?,
                motd_raw = ?, motd_html = ?,
                favicon = ?, last_check = NOW()
             WHERE id = ?',
            [
                $data['online'] ? 1 : 0,
                $data['players_online'] ?? 0,
                $data['players_max'] ?? 0,
                json_encode($data['players_sample'] ?? []),
                $data['version'] ?? null,
                isset($data['motd_raw']) ? json_encode($data['motd_raw']) : null,
                isset($data['motd_html']) ? json_encode($data['motd_html']) : null,
                $data['favicon'] ?? null,
                $serverId
            ]
        );
    }

    /**
     * Получить все серверы, которые нужно пинговать (имеют ссылки из folder_items)
     */
    public function getServersForPing(): array
    {
        return $this->db->fetchAll(
            'SELECT DISTINCT gs.* FROM global_servers gs
             INNER JOIN user_folder_items ufi ON
                JSON_UNQUOTE(JSON_EXTRACT(ufi.settings, "$.server_id")) = gs.id
             WHERE ufi.item_type = "server"'
        );
    }

    /**
     * Получить несколько серверов по ID
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $ph = implode(',', array_fill(0, count($ids), '?'));
        return $this->db->fetchAll(
            "SELECT * FROM global_servers WHERE id IN ($ph)",
            $ids
        );
    }

    /**
     * Массовый пинг - получить серверы по списку адресов
     */
    public function getByAddresses(array $servers): array
    {
        if (empty($servers)) return [];

        $conditions = [];
        $params = [];
        foreach ($servers as $s) {
            $conditions[] = '(address = ? AND port = ?)';
            $params[] = strtolower(trim($s['ip']));
            $params[] = (int)($s['port'] ?? 25565);
        }

        return $this->db->fetchAll(
            'SELECT * FROM global_servers WHERE ' . implode(' OR ', $conditions),
            $params
        );
    }
}
