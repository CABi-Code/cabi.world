<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Класс для работы с ролями и привилегиями пользователей
 */
class Role
{
    public const USER = 'user';
    public const PREMIUM = 'premium';
    public const MODERATOR = 'moderator';
    public const ADMIN = 'admin';

    /**
     * Конфигурация ролей
     */
    private static array $roles = [
        self::USER => [
            'name' => 'Пользователь',
            'short' => null,
            'color' => null,
            'priority' => 0,
        ],
        self::PREMIUM => [
            'name' => 'Премиум',
            'short' => 'PREM',
            'color' => '#22d3ee', // cyan-400
            'priority' => 1,
        ],
        self::MODERATOR => [
            'name' => 'Модератор',
            'short' => 'MOD',
            'color' => '#3b82f6', // blue-500
            'priority' => 2,
        ],
        self::ADMIN => [
            'name' => 'Администратор',
            'short' => 'ADM',
            'color' => '#ef4444', // red-500
            'priority' => 3,
        ],
    ];

    /**
     * Получить конфигурацию роли
     */
    public static function get(string $role): ?array
    {
        return self::$roles[$role] ?? null;
    }

    /**
     * Получить короткое название роли
     */
    public static function getShort(string $role): ?string
    {
        return self::$roles[$role]['short'] ?? null;
    }

    /**
     * Получить цвет роли
     */
    public static function getColor(string $role): ?string
    {
        return self::$roles[$role]['color'] ?? null;
    }

    /**
     * Получить полное название роли
     */
    public static function getName(string $role): string
    {
        return self::$roles[$role]['name'] ?? 'Неизвестно';
    }

    /**
     * Проверить, является ли пользователь модератором или админом
     */
    public static function isModerator(?string $role): bool
    {
        return in_array($role, [self::MODERATOR, self::ADMIN], true);
    }

    /**
     * Проверить, является ли пользователь админом
     */
    public static function isAdmin(?string $role): bool
    {
        return $role === self::ADMIN;
    }

    /**
     * Проверить, является ли пользователь премиум или выше
     */
    public static function isPremium(?string $role): bool
    {
        return in_array($role, [self::PREMIUM, self::MODERATOR, self::ADMIN], true);
    }

    /**
     * Генерирует HTML-бейдж для роли
     */
    public static function badge(?string $role): string
    {
        $config = self::get($role ?? self::USER);
        if (!$config || !$config['short']) {
            return '';
        }

        $color = htmlspecialchars($config['color']);
        $short = htmlspecialchars($config['short']);
        
        return sprintf(
            '<span class="role-badge" style="background:%s">%s</span>',
            $color,
            $short
        );
    }

    /**
     * Получить все роли
     */
    public static function all(): array
    {
        return self::$roles;
    }
}