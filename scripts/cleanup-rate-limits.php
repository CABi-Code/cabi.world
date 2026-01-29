<?php
/**
 * Очистка старых записей rate limiting
 * Запускать по cron каждый час: 0 * * * * php /path/to/scripts/cleanup-rate-limits.php
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$now = time();

// Удаляем старые счётчики (старше часа)
$deleted = $db->execute(
    'DELETE FROM rate_limit_counters WHERE created_at < ?',
    [$now - 3600]
);
echo "Deleted {$deleted} old counters\n";

// Удаляем старые нарушения (старше суток)
$deleted = $db->execute(
    'DELETE FROM rate_limit_violations WHERE updated_at < ?',
    [$now - 86400]
);
echo "Deleted {$deleted} old violations\n";

// Удаляем истёкшие блокировки
$deleted = $db->execute(
    'DELETE FROM rate_limit_blocks WHERE expires_at < ?',
    [$now]
);
echo "Deleted {$deleted} expired blocks\n";

echo "Cleanup complete\n";