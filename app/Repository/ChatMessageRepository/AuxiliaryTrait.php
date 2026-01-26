<?php

namespace App\Repository\ChatMessageRepository;

trait AuxiliaryTrait {

    // =============================================
    // ВСПОМОГАТЕЛЬНЫЕ
    // =============================================

    /**
     * Проверить тайм-аут на отправку сообщений
     */
    public function canSendMessage(int $chatId, int $userId, int $timeout): bool
    {
        if ($timeout <= 0) return true;
        
        $lastMessage = $this->db->fetchOne(
            'SELECT created_at FROM chat_messages 
             WHERE chat_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1',
            [$chatId, $userId]
        );
        
        if (!$lastMessage) return true;
        
        $lastTime = strtotime($lastMessage['created_at']);
        $now = time();
        
        return ($now - $lastTime) >= $timeout;
    }

    /**
     * Получить оставшееся время до возможности отправки
     */
    public function getTimeUntilCanSend(int $chatId, int $userId, int $timeout): int
    {
        if ($timeout <= 0) return 0;
        
        $lastMessage = $this->db->fetchOne(
            'SELECT created_at FROM chat_messages 
             WHERE chat_id = ? AND user_id = ? 
             ORDER BY created_at DESC LIMIT 1',
            [$chatId, $userId]
        );
        
        if (!$lastMessage) return 0;
        
        $lastTime = strtotime($lastMessage['created_at']);
        $now = time();
        $elapsed = $now - $lastTime;
        
        return max(0, $timeout - $elapsed);
    }
}

?>