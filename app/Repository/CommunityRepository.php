<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

class CommunityRepository
{
	
	use \App\Repository\CommunityRepository\CommunityTrait;
	use \App\Repository\CommunityRepository\ChatsTrait;
	use \App\Repository\CommunityRepository\SubscriptionsTrait;
	use \App\Repository\CommunityRepository\ModeratorsTrait;
	use \App\Repository\CommunityRepository\BansTrait;
	use \App\Repository\CommunityRepository\CommunityStructureTrait;
	
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

}
