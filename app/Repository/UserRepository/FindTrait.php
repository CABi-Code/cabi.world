<?php

namespace App\Repository\UserRepository;

use App\Core\DbFields;

trait FindTrait {
	
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
}

?>