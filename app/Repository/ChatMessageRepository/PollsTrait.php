<?php

namespace App\Repository\ChatMessageRepository;

trait PollsTrait {

    // =============================================
    // ОПРОСЫ
    // =============================================

    /**
     * Создать опрос
     */
    public function createPoll(int $messageId, string $question, array $options, bool $isMultiple = false): int
    {
        $this->db->execute(
            'INSERT INTO chat_polls (message_id, question, is_multiple) VALUES (?, ?, ?)',
            [$messageId, $question, $isMultiple ? 1 : 0]
        );
        $pollId = $this->db->lastInsertId();
        
        foreach ($options as $i => $optionText) {
            $this->db->execute(
                'INSERT INTO chat_poll_options (poll_id, option_text, sort_order) VALUES (?, ?, ?)',
                [$pollId, $optionText, $i]
            );
        }
        
        return $pollId;
    }

    /**
     * Получить опрос по message_id
     */
    public function getPoll(int $messageId): ?array
    {
        $poll = $this->db->fetchOne(
            'SELECT * FROM chat_polls WHERE message_id = ?',
            [$messageId]
        );
        
        if (!$poll) return null;
        
        $poll['options'] = $this->db->fetchAll(
            'SELECT * FROM chat_poll_options WHERE poll_id = ? ORDER BY sort_order',
            [$poll['id']]
        );
        
        return $poll;
    }

    /**
     * Проголосовать в опросе
     */
    public function vote(int $optionId, int $userId): bool
    {
        // Получаем информацию о опросе
        $option = $this->db->fetchOne(
            'SELECT po.*, p.is_multiple 
             FROM chat_poll_options po
             JOIN chat_polls p ON po.poll_id = p.id
             WHERE po.id = ?',
            [$optionId]
        );
        
        if (!$option) return false;
        
        // Если не множественный выбор, удаляем предыдущие голоса
        if (!$option['is_multiple']) {
            $this->db->execute(
                'DELETE FROM chat_poll_votes 
                 WHERE user_id = ? AND option_id IN (
                     SELECT id FROM chat_poll_options WHERE poll_id = ?
                 )',
                [$userId, $option['poll_id']]
            );
        }
        
        try {
            $this->db->execute(
                'INSERT INTO chat_poll_votes (option_id, user_id) VALUES (?, ?)',
                [$optionId, $userId]
            );
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) return false;
            throw $e;
        }
    }

    /**
     * Убрать голос
     */
    public function unvote(int $optionId, int $userId): bool
    {
        return $this->db->execute(
            'DELETE FROM chat_poll_votes WHERE option_id = ? AND user_id = ?',
            [$optionId, $userId]
        ) > 0;
    }

    /**
     * Получить голоса пользователя в опросе
     */
    public function getUserVotes(int $pollId, int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT pv.option_id 
             FROM chat_poll_votes pv
             JOIN chat_poll_options po ON pv.option_id = po.id
             WHERE po.poll_id = ? AND pv.user_id = ?',
            [$pollId, $userId]
        );
    }
}

?>