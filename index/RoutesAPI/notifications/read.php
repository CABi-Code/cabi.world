<?php

use App\Repository\NotificationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$notifId = (int) ($input['id'] ?? 0);
$notifRepo = new NotificationRepository();
if ($notifId > 0) {
	$notifRepo->markAsRead($notifId, $user['id']);
} else {
	$notifRepo->markAllAsRead($user['id']);
}
json(['success' => true]);

?>