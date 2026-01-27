<?php

declare(strict_types=1);

namespace App\Validators;

use App\Repository\ApplicationRepository;

class ApplicationValidator
{
    public function validateCreate(array $data): array
    {
        $errors = [];
        
        // Modpack ID
        $modpackId = $data['modpack_id'] ?? null;
        if (empty($modpackId) || !is_numeric($modpackId) || (int)$modpackId <= 0) {
            $errors['modpack_id'] = 'Invalid modpack ID';
        }
        
        // Message
        $message = trim($data['message'] ?? '');
        if (empty($message)) {
            $errors['message'] = 'Введите сообщение';
        } elseif (mb_strlen($message) > ApplicationRepository::MAX_MESSAGE_LENGTH) {
            $errors['message'] = 'Сообщение слишком длинное (максимум ' . ApplicationRepository::MAX_MESSAGE_LENGTH . ' символов)';
        }
        
        // Relevant until
        if (isset($data['relevant_until'])) {
            $validation = $this->validateRelevantUntil($data['relevant_until']);
            if (!$validation['valid']) {
                $errors['relevant_until'] = $validation['error'];
            }
        }
        
        return $errors;
    }

    public function validateUpdate(array $data): array
    {
        $errors = [];
        
        // Message
        $message = trim($data['message'] ?? '');
        if (empty($message)) {
            $errors['message'] = 'Введите сообщение';
        } elseif (mb_strlen($message) > ApplicationRepository::MAX_MESSAGE_LENGTH) {
            $errors['message'] = 'Сообщение слишком длинное (максимум ' . ApplicationRepository::MAX_MESSAGE_LENGTH . ' символов)';
        }
        
        // Relevant until
        if (isset($data['relevant_until'])) {
            $validation = $this->validateRelevantUntil($data['relevant_until']);
            if (!$validation['valid']) {
                $errors['relevant_until'] = $validation['error'];
            }
        }
        
        return $errors;
    }

    private function validateRelevantUntil(?string $date): array
    {
        if ($date === null || $date === '') {
            return ['valid' => true, 'date' => null];
        }
        
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return ['valid' => false, 'error' => 'Некорректная дата'];
        }
        
        $dateObj = new \DateTime($date);
        $now = new \DateTime();
        $maxDate = (clone $now)->modify('+31 days');
        
        if ($dateObj < $now) {
            return ['valid' => false, 'error' => 'Дата не может быть в прошлом'];
        }
        
        if ($dateObj > $maxDate) {
            return ['valid' => false, 'error' => 'Дата не может быть более чем на 31 день вперёд'];
        }
        
        return ['valid' => true, 'date' => $dateObj->format('Y-m-d')];
    }
}
