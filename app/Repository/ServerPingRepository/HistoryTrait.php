<?php

namespace App\Repository\ServerPingRepository;

trait HistoryTrait
{
    /**
     * Получить историю статуса сервера для графика
     */
    public function getHistory(int $itemId, int $hours = 24): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        // Получаем все записи за период
        $records = $this->db->fetchAll(
            "SELECT * FROM server_ping_history 
             WHERE item_id = ? AND checked_at >= ?
             ORDER BY checked_at ASC",
            [$itemId, $since]
        );
        
        // Группируем по минутам для графика
        return $this->aggregateByMinute($records);
    }
    
    /**
     * Агрегировать данные по минутам
     */
    private function aggregateByMinute(array $records): array
    {
        $result = [];
        $lastFullRecord = null;
        
        foreach ($records as $record) {
            $minute = substr($record['checked_at'], 0, 16); // Y-m-d H:i
            
            // Если это полная запись - сохраняем её
            if (!$record['is_same_as_previous']) {
                $lastFullRecord = $record;
            }
            
            // Используем последние полные данные
            $data = $lastFullRecord ?: $record;
            
            $result[$minute] = [
                'time' => $minute,
                'online' => (bool)$data['is_online'],
                'players' => (int)$data['players_online'],
                'max' => (int)$data['players_max']
            ];
        }
        
        return array_values($result);
    }
    
    /**
     * Получить статистику за день
     */
    public function getDailyStats(int $itemId, string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';
        
        // Средние и максимальные значения
        $stats = $this->db->fetchOne(
            "SELECT 
                AVG(CASE WHEN is_same_as_previous = 0 THEN players_online END) as avg_players,
                MAX(CASE WHEN is_same_as_previous = 0 THEN players_online END) as max_players,
                SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as online_checks,
                COUNT(*) as total_checks
             FROM server_ping_history 
             WHERE item_id = ? AND checked_at BETWEEN ? AND ?",
            [$itemId, $start, $end]
        );
        
        // Уникальные игроки за день
        $playerRecords = $this->db->fetchAll(
            "SELECT players_sample FROM server_ping_history 
             WHERE item_id = ? AND checked_at BETWEEN ? AND ? 
             AND is_same_as_previous = 0 AND players_sample IS NOT NULL",
            [$itemId, $start, $end]
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
     * Очистить старые записи (старше 7 дней)
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
