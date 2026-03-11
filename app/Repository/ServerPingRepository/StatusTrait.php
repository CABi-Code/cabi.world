<?php

namespace App\Repository\ServerPingRepository;

trait StatusTrait
{
    /**
     * Сохранить статус сервера (привязка к global_servers)
     */
    public function saveStatus(int $serverId, array $data, ?int $itemId = null): bool
    {
        // Получаем последний полный статус
        $last = $this->getLastStatus($serverId);

        // Проверяем, изменились ли данные
        $hasChanged = $this->hasStatusChanged($last, $data);

        // Обновляем глобальный сервер
        $this->updateGlobalServer($serverId, $data);

        // Записываем в историю только если есть привязка к элементу папки
        if ($itemId !== null && $itemId > 0) {
            if ($hasChanged) {
                $this->insertHistoryRecord($serverId, $data, false, $itemId);
            } else {
                $this->insertHistoryRecord($serverId, $data, true, $itemId);
            }
        }

        return true;
    }

    /**
     * Получить последний полный статус сервера (из history)
     */
    public function getLastStatus(int $serverId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM server_ping_history
             WHERE server_id = ? AND is_same_as_previous = 0
             ORDER BY checked_at DESC LIMIT 1",
            [$serverId]
        );
    }

    /**
     * Получить текущий статус из global_servers
     */
    public function getCurrentStatus(int $serverId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM global_servers WHERE id = ?",
            [$serverId]
        );
    }

    /**
     * Проверить, изменился ли статус
     */
    private function hasStatusChanged(?array $last, array $current): bool
    {
        if (!$last) return true;

        if ((bool)$last['is_online'] !== (bool)$current['online']) return true;
        if ((int)($last['players_online'] ?? 0) !== (int)$current['players_online']) return true;
        if ((int)($last['players_max'] ?? 0) !== (int)$current['players_max']) return true;

        // Сравниваем список игроков
        $lastPlayers = $last['players_sample'] ? json_decode($last['players_sample'], true) : [];
        $currentPlayers = $current['players_sample'] ?? [];

        $lastNames = array_column($lastPlayers, 'name');
        $currentNames = array_column($currentPlayers, 'name');

        sort($lastNames);
        sort($currentNames);

        if ($lastNames !== $currentNames) return true;

        return false;
    }

    /**
     * Обновить глобальный сервер
     */
    private function updateGlobalServer(int $serverId, array $data): void
    {
        $this->db->execute(
            "UPDATE global_servers SET
                is_online = ?, players_online = ?, players_max = ?,
                players_sample = ?, version = ?,
                favicon = ?, last_check = NOW()
             WHERE id = ?",
            [
                $data['online'] ? 1 : 0,
                $data['players_online'] ?? 0,
                $data['players_max'] ?? 0,
                json_encode($data['players_sample'] ?? []),
                $data['version'] ?? null,
                $data['favicon'] ?? null,
                $serverId
            ]
        );
    }

    /**
     * Вставить запись в историю
     * При is_same_as_previous=1 не сохраняем дублирующие данные (NULL)
     */
    private function insertHistoryRecord(int $serverId, array $data, bool $isSame, int $itemId): void
    {
        if ($isSame) {
            // Оптимизация: только отметка времени и флаг
            $this->db->execute(
                "INSERT INTO server_ping_history
                 (server_id, item_id, is_online, players_online, players_max, players_sample, version, source, is_same_as_previous, checked_at)
                 VALUES (?, ?, ?, NULL, NULL, NULL, NULL, ?, 1, NOW())",
                [
                    $serverId,
                    $itemId,
                    $data['online'] ? 1 : 0,
                    $data['source'] ?? 'server'
                ]
            );
        } else {
            // Полная запись
            $this->db->execute(
                "INSERT INTO server_ping_history
                 (server_id, item_id, is_online, players_online, players_max, players_sample, version, source, is_same_as_previous, checked_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())",
                [
                    $serverId,
                    $itemId,
                    $data['online'] ? 1 : 0,
                    $data['players_online'],
                    $data['players_max'],
                    json_encode($data['players_sample'] ?? []),
                    $data['version'] ?? null,
                    $data['source'] ?? 'server'
                ]
            );
        }
    }
}
