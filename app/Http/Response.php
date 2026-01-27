<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        // Добавляем ошибки БД если есть
        if (class_exists('App\Core\Database')) {
            $dbErrors = \App\Core\Database::getErrors();
            if (!empty($dbErrors)) {
                $data['_db_errors'] = $dbErrors;
            }
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    public static function view(string $template, array $data = []): void
    {
        extract($data);
        require TEMPLATES_PATH . '/' . $template . '.php';
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['error' => $message], $status);
    }

    public static function errors(array $errors, int $status = 400): void
    {
        self::json(['errors' => $errors], $status);
    }

    public static function success(array $data = [], int $status = 200): void
    {
        self::json(array_merge(['success' => true], $data), $status);
    }
}
