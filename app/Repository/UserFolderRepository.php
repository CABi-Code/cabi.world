<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class UserFolderRepository
{
    use UserFolderRepository\ItemsTrait;
    use UserFolderRepository\FoldersTrait;
    use UserFolderRepository\ApplicationsTrait;
    use UserFolderRepository\StructureTrait;
    use UserFolderRepository\SubscriptionsTrait;
    
    protected Database $db;
    
    public const ENTITY_TYPES = ['folder', 'modpack', 'mod'];
    public const ELEMENT_TYPES = ['server', 'chat', 'application', 'shortcut'];
    public const CATEGORY_MAIN_APPS = 'main_applications';
    public const CATEGORY_MODPACKS = 'modpacks';
    public const ITEMS_MAP = [
		'folder' => ['icon' => 'folder', 'color' => '#eab308', 
					'label' => 'Папка', 'descriptions' => 'Группируйте элементы'],
		'chat' => ['icon' => 'message-circle', 'color' => '#28A745',
					'label' => 'Чат', 'descriptions' => 'Общение с подписчиками'],
		'modpack' => ['icon' => 'package', 'color' => '#8b5cf6', 
					'label' => 'Модпак', 'descriptions' => 'Выберите из каталога'],
		'mod' => ['icon' => 'puzzle', 'color' => '#10b981', 
					'label' => 'Мод', 'descriptions' => 'Добавьте модификацию'],
		'server' => ['icon' => 'server', 'color' => '#f59e0b', 
					'label' => 'Сервер', 'descriptions' => 'Добавьте Minecraft сервер'],
		'application' => ['icon' => 'file-text', 'color' => '#3b82f6',
					'label' => 'Заметка', 'descriptions' => 'Текстовый документ'],
		'shortcut' => ['icon' => 'link', 'color' => '#6366f1', 
					'label' => 'Ярлык', 'descriptions' => 'Внешняя ссылка'],
	];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isEntity(string $type): bool
    {
        return in_array($type, self::ENTITY_TYPES);
    }

    public function isElement(string $type): bool
    {
        return in_array($type, self::ELEMENT_TYPES);
    }
	
    public function getItemsMap(): array
    {
        return self::ITEMS_MAP;
    }
}
