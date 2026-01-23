<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $pdo;
    private static array $queryLog = [];
    private static array $errors = [];

    private function __construct()
    {
        $config = require CONFIG_PATH . '/database.php';
        
        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'], $config['host'], $config['port'],
            $config['database'], $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            self::$errors[] = ['type' => 'connection', 'message' => $e->getMessage()];
            throw $e;
        }
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            self::$queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => round((microtime(true) - $start) * 1000, 2) . 'ms',
                'rows' => $stmt->rowCount()
            ];
            return $stmt;
        } catch (PDOException $e) {
            self::$errors[] = [
                'type' => 'query',
                'sql' => $sql,
                'params' => $params,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
            throw $e;
        }
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->query($sql, $params)->fetch() ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollBack(): void { $this->pdo->rollBack(); }

    public static function getLog(): array
    {
        return self::$queryLog;
    }

    public static function getErrors(): array
    {
        return self::$errors;
    }

    public static function getDebugInfo(): array
    {
        return [
            'queries' => self::$queryLog,
            'errors' => self::$errors,
            'total_queries' => count(self::$queryLog),
            'total_errors' => count(self::$errors)
        ];
    }
}
