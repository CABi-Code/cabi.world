<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class ChatMessageRepository
{
	use \App\Repository\ChatMessageRepository\MessagesTrait;
	use \App\Repository\ChatMessageRepository\ImagesTrait;
	use \App\Repository\ChatMessageRepository\PollsTrait;
	use \App\Repository\ChatMessageRepository\AuxiliaryTrait;

    private Database $db;
    
    public const MAX_IMAGES = 4;
    public const MAX_MESSAGE_LENGTH = 2000;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

}
