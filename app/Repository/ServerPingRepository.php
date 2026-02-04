<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class ServerPingRepository
{
    use ServerPingRepository\StatusTrait;
    use ServerPingRepository\HistoryTrait;
    
    protected Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
