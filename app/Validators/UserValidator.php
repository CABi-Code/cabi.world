<?php

declare(strict_types=1);

namespace App\Validators;

class UserValidator
{
    public function validateUpdate(array $data): array
    {
        $errors = [];
        
        // Username
        if (isset($data['username'])) {
            $username = trim($data['username']);
            if (mb_strlen($username) < 2 || mb_strlen($username) > 30) {
                $errors['username'] = 'Имя должно быть от 2 до 30 символов';
            } elseif (!preg_match('/^[\p{L}\p{N}\s]+$/u', $username)) {
                $errors['username'] = 'Имя содержит недопустимые спецсимволы';
            }
        }
        
        // Bio
        if (isset($data['bio']) && mb_strlen($data['bio']) > 500) {
            $errors['bio'] = 'Биография слишком длинная (максимум 500 символов)';
        }
        
        // Contacts
        if (isset($data['discord']) && mb_strlen($data['discord']) > 100) {
            $errors['discord'] = 'Discord слишком длинный';
        }
        
        if (isset($data['telegram']) && mb_strlen($data['telegram']) > 100) {
            $errors['telegram'] = 'Telegram слишком длинный';
        }
        
        if (isset($data['vk']) && mb_strlen($data['vk']) > 100) {
            $errors['vk'] = 'VK слишком длинный';
        }
        
        return $errors;
    }
}
