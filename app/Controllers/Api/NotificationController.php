<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Repository\NotificationRepository;

class NotificationController
{
    private NotificationRepository $notifRepo;

    public function __construct()
    {
        $this->notifRepo = new NotificationRepository();
    }

    public function index(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $notifications = $this->notifRepo->findByUser($user['id']);
        Response::json([
            'success' => true,
            'notifications' => $notifications,
            'unread' => $this->notifRepo->countUnread($user['id'])
        ]);
    }

    public function read(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            Response::error('Unauthorized', 401);
            return;
        }

        $id = (int)($request->get('id', 0));
        if ($id > 0) {
            $this->notifRepo->markAsRead($id, $user['id']);
        } else {
            $this->notifRepo->markAllAsRead($user['id']);
        }

        Response::json(['success' => true]);
    }
}
