<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\CommunityRepository;
use App\Repository\ChatMessageRepository;
use App\Service\ImageService;
use App\Core\Role;

class ChatController
{
    use \App\Controllers\Api\ChatController\MessagesTrait;
    use \App\Controllers\Api\ChatController\ActionsTrait;

    protected CommunityRepository $communityRepo;
    protected ChatMessageRepository $messageRepo;
    protected ImageService $imageService;

    public function __construct()
    {
        $this->communityRepo = new CommunityRepository();
        $this->messageRepo = new ChatMessageRepository();
        $this->imageService = new ImageService();
    }
}
