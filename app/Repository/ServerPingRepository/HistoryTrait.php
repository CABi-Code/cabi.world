<?php

namespace App\Repository\ServerPingRepository;

trait HistoryTrait
{
    /**
     * Получить историю статуса сервера для графика
     * Поддерживает hours=0 для "за всё время"
     */
    public function getHistory(int $serverId, int $hours = 24): array
    {
        if ($hours <= 0) {
            // За всё время
            $records = $this->db->fetchAll(
                "SELECT * FROM server_ping_history
                 WHERE server_id = ?
                 ORDER BY checked_at ASC",
                [$serverId]
            );
        } else {
            $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            $records = $this->db->fetchAll(
                "SELECT * FROM server_ping_history
                 WHERE server_id = ? AND checked_at >= ?
                 ORDER BY checked_at ASC",
                [$serverId, $since]
            );
        }

        // Выбираем интервал группировки в зависимости от периода
        $interval = $this->getAggregationInterval($hours, count($records));

        return $this->aggregateByInterval($records, $interval);
    }

    /**
     * Совместимость: получить историю по item_id
     */
    public function getHistoryByItemId(int $itemId, int $hours = 24): array
    {
        // Находим server_id из item settings
        $item = $this->db->fetchOne(
            "SELECT settings FROM user_folder_items WHERE id = ?",
            [$itemId]
        );

        if (!$item || !$item['settings']) return [];

        $settings = json_decode($item['settings'], true);
        $serverId = $settings['server_id'] ?? null;

        if (!$serverId) {
            // Фоллбэк: ищем по item_id в истории (старые данные)
            return $this->getHistoryByItemIdLegacy($itemId, $hours);
        }

        return $this->getHistory($serverId, $hours);
    }

    /**
     * Фоллбэк для старых записей без server_id
     */
    private function getHistoryByItemIdLegacy(int $itemId, int $hours = 24): array
    {
        if ($hours <= 0) {
            $records = $this->db->fetchAll(
                "SELECT * FROM server_ping_history
                 WHERE item_id = ?
                 ORDER BY checked_at ASC",
                [$itemId]
            );
        } else {
            $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            $records = $this->db->fetchAll(
                "SELECT * FROM server_ping_history
                 WHERE item_id = ? AND checked_at >= ?
                 ORDER BY checked_at ASC",
                [$itemId, $since]
            );
        }

        $interval = $this->getAggregationInterval($hours, count($records));
        return $this->aggregateByInterval($records, $interval);
    }

    /**
     * Определить интервал группировки
     */
    private function getAggregationInterval(int $hours, int $recordCount): string
    {
        if ($hours <= 0) {
            // Все время — группируем по часам или дням
            if ($recordCount > 5000) return 'day';
            if ($recordCount > 1000) return 'hour';
            return '10min';
        }
        if ($hours <= 6) return 'minute';
        if ($hours <= 24) return '5min';
        if ($hours <= 72) return '10min';
        if ($hours <= 168) return '30min';
        return 'hour';
    }

    /**
     * Агрегировать данные по интервалу
     * Восстанавливает данные для is_same_as_previous записей
     */
    private function aggregateByInterval(array $records, string $interval): array
    {
        $result = [];
        $lastFullRecord = null;

        foreach ($records as $record) {
            $key = $this->getIntervalKey($record['checked_at'], $interval);

            // Восстанавливаем данные из последней полной записи
            if (!$record['is_same_as_previous']) {
                $lastFullRecord = $record;
            }

            $data = $lastFullRecord ?: $record;

            // Для каждого интервала берём последнее/максимальное значение
            if (!isset($result[$key])) {
                $result[$key] = [
                    'time' => $key,
                    'online' => (bool)$data['is_online'],
                    'players' => (int)($data['players_online'] ?? 0),
                    'max' => (int)($data['players_max'] ?? 0),
                    'count' => 1,
                ];
            } else {
                // Обновляем — берём последние данные для интервала
                $result[$key]['online'] = (bool)$data['is_online'];
                $result[$key]['players'] = max($result[$key]['players'], (int)($data['players_online'] ?? 0));
                $result[$key]['max'] = (int)($data['players_max'] ?? 0);
                $result[$key]['count']++;
            }
        }

        // Убираем count из вывода
        return array_map(function ($r) {
            unset($r['count']);
            return $r;
        }, array_values($result));
    }

    /**
     * Получить ключ интервала для группировки
     */
    private function getIntervalKey(string $datetime, string $interval): string
    {
        $ts = strtotime($datetime);

        switch ($interval) {
            case 'minute':
                return date('Y-m-d H:i', $ts);
            case '5min':
                $m = (int)date('i', $ts);
                $m = $m - ($m % 5);
                return date('Y-m-d H:', $ts) . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            case '10min':
                $m = (int)date('i', $ts);
                $m = $m - ($m % 10);
                return date('Y-m-d H:', $ts) . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            case '30min':
                $m = (int)date('i', $ts);
                $m = $m - ($m % 30);
                return date('Y-m-d H:', $ts) . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            case 'hour':
                return date('Y-m-d H:00', $ts);
            case 'day':
                return date('Y-m-d', $ts);
            default:
                return date('Y-m-d H:i', $ts);
        }
    }

    /**
     * Получить статистику за день
     */
    public function getDailyStats(int $serverId, string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';

        $stats = $this->db->fetchOne(
            "SELECT
                AVG(CASE WHEN is_same_as_previous = 0 THEN players_online END) as avg_players,
                MAX(CASE WHEN is_same_as_previous = 0 THEN players_online END) as max_players,
                SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as online_checks,
                COUNT(*) as total_checks
             FROM server_ping_history
             WHERE server_id = ? AND checked_at BETWEEN ? AND ?",
            [$serverId, $start, $end]
        );

        $playerRecords = $this->db->fetchAll(
            "SELECT players_sample FROM server_ping_history
             WHERE server_id = ? AND checked_at BETWEEN ? AND ?
             AND is_same_as_previous = 0 AND players_sample IS NOT NULL",
            [$serverId, $start, $end]
        );

        $uniquePlayers = [];
        foreach ($playerRecords as $record) {
            $players = json_decode($record['players_sample'], true) ?: [];
            foreach ($players as $player) {
                if (isset($player['name'])) {
                    $uniquePlayers[$player['name']] = true;
                }
            }
        }

        return [
            'date' => $date,
            'avg_players' => round((float)($stats['avg_players'] ?? 0), 1),
            'max_players' => (int)($stats['max_players'] ?? 0),
            'uptime_percent' => $stats['total_checks'] > 0
                ? round(($stats['online_checks'] / $stats['total_checks']) * 100, 1)
                : 0,
            'unique_players' => count($uniquePlayers),
            'player_names' => array_keys($uniquePlayers)
        ];
    }

    /**
     * Очистить старые записи
     */
    public function cleanup(int $daysToKeep = 7): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        return $this->db->execute(
            "DELETE FROM server_ping_history WHERE checked_at < ?",
            [$cutoff]
        );
    }
}
