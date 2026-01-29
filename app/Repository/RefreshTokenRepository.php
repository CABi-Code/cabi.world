<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class RefreshTokenRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $tokenHash, int $lifetime, ?string $fingerprint = null): void
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $lifetime);
        $this->db->execute(
            'INSERT INTO refresh_tokens (user_id, token_hash, fingerprint, expires_at) VALUES (?, ?, ?, ?)',
            [$userId, $tokenHash, $fingerprint, $expiresAt]
        );
    }

    public function findByHash(string $tokenHash): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM refresh_tokens WHERE token_hash = ? AND expires_at > NOW()',
            [$tokenHash]
        );
    }

    public function revoke(string $tokenHash): void
    {
        $this->db->execute('UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = ?', [$tokenHash]);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->db->execute('UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ?', [$userId]);
    }
}
