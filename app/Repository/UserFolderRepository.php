<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

/**
 * Репозиторий для работы с "Моей папкой" пользователя
 */
class UserFolderRepository
{
    use UserFolderRepository\ItemsTrait;
    use UserFolderRepository\FoldersTrait;
    use UserFolderRepository\ApplicationsTrait;
    use UserFolderRepository\StructureTrait;
    use UserFolderRepository\SubscriptionsTrait;
    
    protected Database $db;
    
    public const ENTITY_TYPES = ['folder', 'chat', 'modpack', 'mod'];
    public const ELEMENT_TYPES = ['server', 'application', 'shortcut'];
    public const CATEGORY_MAIN_APPS = 'main_applications';
    public const CATEGORY_MODPACKS = 'modpacks';

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
}
