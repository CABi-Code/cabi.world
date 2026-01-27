<?php

namespace App\Repository\UserRepository;

trait UpdateTrait {
	
    public function update(int $id, array $data): array
    {
        $allowed = ['username', 'bio', 'avatar', 'banner', 'discord', 'telegram', 'vk',
                    'theme', 'view_mode', 'profile_bg_type', 'profile_bg_value', 
                    'avatar_bg_type', 'avatar_bg_value', 'banner_bg_type', 'banner_bg_value'];
        $sets = [];
        $params = [];
		$errors = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
				
				if ($key == 'username' && !preg_match('/^[\p{L}\p{N}\s]+$/u', $value)) {
					$errors['Username'] = ['msg' => 'Имя содержит недопустимые спецсимволы', 'code' => 422];
				}
				$lastUpdate = $this->db->query("SELECT updated_at FROM users WHERE id = ? AND updated_at > NOW() - INTERVAL 1 MINUTE", [$id])->fetch();
				
				if ($lastUpdate) {
					$errors['LastUpdate'] = ['msg' => 'Подожди минуту, прежде чем сделать это', 'code' => 429];
				}
				
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }
		
		if (!empty($errors)) {
			return ['errors' => $errors];
		}
		
        if (empty($sets)) return ['errors' => ['Bad Request' => ['msg' => 'Сервер не смог понять запрос из-за недействительного синтаксиса', 'code' => 400]]];

        $params[] = $id;
		$a = $this->db->execute('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params) > 0;
        if($a) {			
			return ['success' => true];
		} else {
			return ['errors' => ['Conflict' => ['msg' => 'Не удалось обновить параметры, возможно вы ничего не изменили', 'code' => 409]]];
		}
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