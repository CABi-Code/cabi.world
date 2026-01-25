<?php

namespace App\Repository\ApplicationRepository;

trait ValidateRelevantUntilTrait {

    /**
     * Валидация даты актуальности
     */
    public function validateRelevantUntil(?string $date): array
    {
        if (empty($date)) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата актуальности обязательна'
            ];
        }
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Неверный формат даты'
            ];
        }
        
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Некорректная дата'
            ];
        }
        
        $parsedDate = date('Y-m-d', $timestamp);
        if ($parsedDate !== $date) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Некорректная дата'
            ];
        }
        
        $maxTimestamp = strtotime('+' . self::MAX_RELEVANCE_DAYS . ' days');
        $minTimestamp = strtotime('today');
        
        if ($timestamp < $minTimestamp) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата не может быть в прошлом'
            ];
        }
        
        if ($timestamp > $maxTimestamp) {
            return [
                'valid' => false,
                'date' => null,
                'error' => 'Дата не может быть больше чем через ' . self::MAX_RELEVANCE_DAYS . ' дней'
            ];
        }
        
        return [
            'valid' => true,
            'date' => $parsedDate,
            'error' => null
        ];
    }
}

?>