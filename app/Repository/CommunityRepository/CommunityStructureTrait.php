<?php

namespace App\Repository\CommunityRepository;

trait CommunityStructureTrait {

    // =============================================
    // СТРУКТУРА СООБЩЕСТВА
    // =============================================

    /**
     * Получить полную структуру сообщества (папки + чаты)
     */
    public function getStructure(int $communityId): array
    {
        $folders = $this->db->fetchAll(
            'SELECT * FROM community_folders WHERE community_id = ? ORDER BY sort_order, name',
            [$communityId]
        );
        
        $chats = $this->db->fetchAll(
            'SELECT * FROM community_chats WHERE community_id = ? ORDER BY sort_order, name',
            [$communityId]
        );
        
        // Строим дерево
        $structure = $this->buildTree($folders, $chats);
        
        return $structure;
    }

    /**
     * Рекурсивно строит дерево папок и чатов
     */
    private function buildTree(array $folders, array $chats, ?int $parentId = null): array
    {
        $result = [];
        
        // Добавляем папки текущего уровня
        foreach ($folders as $folder) {
            if ($folder['parent_id'] == $parentId) {
                $item = [
                    'type' => 'folder',
                    'data' => $folder,
                    'children' => $this->buildTree($folders, $chats, $folder['id'])
                ];
                // Добавляем чаты в папку
                foreach ($chats as $chat) {
                    if ($chat['folder_id'] == $folder['id']) {
                        $item['children'][] = [
                            'type' => 'chat',
                            'data' => $chat
                        ];
                    }
                }
                $result[] = $item;
            }
        }
        
        // Если это корень, добавляем чаты без папки
        if ($parentId === null) {
            foreach ($chats as $chat) {
                if ($chat['folder_id'] === null) {
                    $result[] = [
                        'type' => 'chat',
                        'data' => $chat
                    ];
                }
            }
        }
        
        return $result;
    }
}

?>