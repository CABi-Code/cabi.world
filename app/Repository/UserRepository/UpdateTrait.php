<?php

namespace App\Repository\UserRepository;

trait UpdateTrait {
	
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
	
}

?>