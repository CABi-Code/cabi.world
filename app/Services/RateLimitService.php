<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class RateLimitService
{
    private Database $db;
    private array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require CONFIG_PATH . '/app.php';
    }

    /**
     * Проверяет лимит и возвращает статус
     */
    public function check(string $identifier, string $type = 'global'): array
    {
		error_log("RateLimitService::check called - identifier: {$identifier}, type: {$type}");
        $limits = $this->config['rate_limit'][$type] ?? $this->config['rate_limit']['global'];
        $key = $this->getKey($identifier, $type);
        
        // Проверяем блокировку
        $block = $this->getBlock($key);
        if ($block) {
            return [
                'allowed' => false,
                'blocked' => true,
                'requires_captcha' => true,
                'retry_after' => $block['expires_at'] - time(),
                'reason' => 'rate_limit_exceeded'
            ];
        }
        
        // Считаем запросы
        $count = $this->incrementCounter($key, $limits['window']);
        
        if ($count > $limits['requests']) {
            // Превышен лимит — увеличиваем счётчик нарушений
            $violations = $this->incrementViolations($identifier);
            
            if ($violations >= $this->config['rate_limit']['captcha_threshold']) {
                // Блокируем и требуем капчу
                $this->createBlock($key, $limits['block_duration']);
                
                return [
                    'allowed' => false,
                    'blocked' => true,
                    'requires_captcha' => true,
                    'retry_after' => $limits['block_duration'],
                    'reason' => 'too_many_violations'
                ];
            }
            
            return [
                'allowed' => false,
                'blocked' => false,
                'requires_captcha' => false,
                'retry_after' => $limits['window'],
                'reason' => 'rate_limit_exceeded'
            ];
        }
        
        return [
            'allowed' => true,
            'blocked' => false,
            'requires_captcha' => false,
            'remaining' => $limits['requests'] - $count
        ];
    }

    /**
     * Проверяет, нужна ли капча пользователю
     */
    public function requiresCaptcha(string $identifier): bool
    {
        $key = $this->getKey($identifier, 'global');
        $block = $this->getBlock($key);
        
        return $block !== null && !$block['captcha_solved'];
    }

    /**
     * Отмечает, что капча пройдена
     */
    public function solveCaptcha(string $identifier): void
    {
        $this->db->execute(
            'UPDATE rate_limit_blocks SET captcha_solved = 1 WHERE identifier = ?',
            [$identifier]
        );
        
        // Сбрасываем счётчик нарушений
        $this->db->execute(
            'DELETE FROM rate_limit_violations WHERE identifier = ?',
            [$identifier]
        );
    }

    /**
     * Получает информацию о блокировке
     */
    public function getBlockInfo(string $identifier): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM rate_limit_blocks WHERE identifier = ? AND expires_at > ? AND captcha_solved = 0',
            [$identifier, time()]
        );
    }

    private function getKey(string $identifier, string $type): string
    {
        return "{$type}:{$identifier}";
    }

    private function incrementCounter(string $key, int $window): int
    {
        $now = time();
        $windowStart = $now - $window;
        
        // Удаляем старые записи
        $this->db->execute(
            'DELETE FROM rate_limit_counters WHERE key_name = ? AND created_at < ?',
            [$key, $windowStart]
        );
        
        // Добавляем новую запись
        $this->db->execute(
            'INSERT INTO rate_limit_counters (key_name, created_at) VALUES (?, ?)',
            [$key, $now]
        );
        
        // Считаем
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) as cnt FROM rate_limit_counters WHERE key_name = ? AND created_at >= ?',
            [$key, $windowStart]
        );
        
        return (int)($result['cnt'] ?? 0);
    }

    private function incrementViolations(string $identifier): int
    {
        $this->db->execute(
            'INSERT INTO rate_limit_violations (identifier, count, updated_at) 
             VALUES (?, 1, ?) 
             ON DUPLICATE KEY UPDATE count = count + 1, updated_at = ?',
            [$identifier, time(), time()]
        );
        
        $result = $this->db->fetchOne(
            'SELECT count FROM rate_limit_violations WHERE identifier = ?',
            [$identifier]
        );
        
        return (int)($result['count'] ?? 0);
    }

    private function getBlock(string $key): ?array
    {
        $identifier = explode(':', $key, 2)[1] ?? $key;
        
        return $this->db->fetchOne(
            'SELECT *, expires_at - ? as remaining FROM rate_limit_blocks 
             WHERE identifier = ? AND expires_at > ? AND captcha_solved = 0',
            [time(), $identifier, time()]
        );
    }

    private function createBlock(string $key, int $duration): void
    {
        $identifier = explode(':', $key, 2)[1] ?? $key;
        $expiresAt = time() + $duration;
        
        $this->db->execute(
            'INSERT INTO rate_limit_blocks (identifier, expires_at, captcha_solved, created_at) 
             VALUES (?, ?, 0, ?)
             ON DUPLICATE KEY UPDATE expires_at = ?, captcha_solved = 0',
            [$identifier, $expiresAt, time(), $expiresAt]
        );
    }
}