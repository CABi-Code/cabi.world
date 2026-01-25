<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Core\DbFields;

class UserRepository
{
	use \App\Repository\UserRepository\FindTrait;
	use \App\Repository\UserRepository\UpdateTrait;
	
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
