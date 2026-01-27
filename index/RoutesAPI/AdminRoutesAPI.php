<?php

use App\Middleware\AdminMiddleware;
use App\Repository\ApplicationRepository;
use App\Repository\NotificationRepository;

$adminMiddleware = new AdminMiddleware();
$adminUser = $adminMiddleware->requireModerator();

$adminRoute = substr($apiRoute, 7); // Remove '/admin/'



switch (true) {
	// Установка статуса заявки
	case $adminRoute === 'application/status' && $method === 'POST':
		$appId = (int) ($input['id'] ?? 0);
		$status = $input['status'] ?? '';
		
		if ($appId <= 0) json(['error' => 'Invalid ID'], 400);
		if (!in_array($status, ['pending', 'accepted', 'rejected'])) {
			json(['error' => 'Invalid status'], 400);
		}
		
		$appRepo = new ApplicationRepository();
		$application = $appRepo->findById($appId);
		
		if (!$application) {
			json(['error' => 'Application not found'], 404);
		}
		
		$result = $appRepo->setStatusByModerator($appId, $status);
		
		if ($result) {
			// Отправляем уведомление пользователю
			$notifRepo = new NotificationRepository();
			if ($status === 'accepted') {
				$notifRepo->notifyApplicationAccepted(
					$application['user_id'],
					$application['modpack_name'],
					$application['slug'],
					$application['platform']
				);
			} elseif ($status === 'rejected') {
				$notifRepo->notifyApplicationRejected(
					$application['user_id'],
					$application['modpack_name']
				);
			}
			
			json(['success' => true]);
		}
		
		json(['error' => 'Failed to update status'], 500);
		break;
	
	// Получение детальной информации о заявке
	case preg_match('#^application/(\d+)$#', $adminRoute, $matches):
		$appId = (int) $matches[1];
		$appRepo = new ApplicationRepository();
		$application = $appRepo->findById($appId);
		
		if (!$application) {
			json(['error' => 'Not found'], 404);
		}
		
		json(['success' => true, 'application' => $application]);
		break;
	
	default:
		json(['error' => 'Admin route not found'], 404);
}
exit;

?>