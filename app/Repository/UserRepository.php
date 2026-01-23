<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(string $login, string $email, string $passwordHash, string $username): int
    {
        $this->db->execute(
            'INSERT INTO users (login, email, password_hash, username, created_at) VALUES (?, ?, ?, ?, NOW())',
            [$login, $email, $passwordHash, $username]
        );
        return $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::USER_PUBLIC . ' FROM users WHERE id = ?',
            [$id]
        );
    }

    public function findByLogin(string $login): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::USER_PUBLIC . ' FROM users WHERE login = ?',
            [$login]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::USER_PUBLIC . ' FROM users WHERE email = ?',
            [$email]
        );
    }

    public function findForAuth(string $loginOrEmail): ?array
    {
        return $this->db->fetchOne(
            'SELECT ' . DbFields::USER_AUTH . ' FROM users WHERE login = ? OR email = ?',
            [$loginOrEmail, $loginOrEmail]
        );
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['username', 'bio', 'avatar', 'banner', 'discord', 'telegram', 'vk',
                    'theme', 'view_mode', 'profile_bg_type', 'profile_bg_value', 
                    'avatar_bg_type', 'avatar_bg_value', 'banner_bg_type', 'banner_bg_value'];
        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        return $this->db->execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params) > 0;
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        return $this->db->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $id]) > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public function loginExists(string $login): bool
    {
        return (bool) $this->db->fetchOne('SELECT 1 FROM users WHERE login = ?', [$login]);
    }

    public function emailExists(string $email): bool
    {
        return (bool) $this->db->fetchOne('SELECT 1 FROM users WHERE email = ?', [$email]);
    }

    public function validateProfileFiles(int $id, string $uploadPath): array
    {
        $user = $this->findById($id);
        if (!$user) return [];

        $updates = [];
        
        if ($user['avatar'] && !file_exists($uploadPath . $user['avatar'])) {
            $updates['avatar'] = null;
        }
        
        if ($user['banner'] && !file_exists($uploadPath . $user['banner'])) {
            $updates['banner'] = null;
        }

        if (!empty($updates)) {
            $this->update($id, $updates);
        }

        return $updates;
    }
}
