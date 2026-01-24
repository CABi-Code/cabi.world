<?php

use App\Repository\NotificationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$notifRepo = new NotificationRepository();
$notifications = $notifRepo->findByUser($user['id']);
json(['success' => true, 'notifications' => $notifications, 'unread' => $notifRepo->countUnread($user['id'])]);

?>