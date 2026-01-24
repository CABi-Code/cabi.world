<?php

use App\Repository\ApplicationRepository;
use App\Service\ImageService;

if (!$user) json(['error' => 'Unauthorized'], 401);
$modpackId = (int) ($input['modpack_id'] ?? 0);
if ($modpackId <= 0) json(['error' => 'Invalid modpack ID'], 400);

$appRepo = new ApplicationRepository();

if ($appRepo->userHasApplied($modpackId, $user['id'])) {
	json(['error' => 'Вы уже подали заявку'], 400);
}

$dateValidation = $appRepo->validateRelevantUntil($input['relevant_until'] ?? null);
if (!$dateValidation['valid']) {
	json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
}

if (empty(trim($input['message'] ?? ''))) {
	json(['errors' => ['message' => 'Введите сообщение']], 400);
}

try {
	$appId = $appRepo->create(
		$modpackId,
		$user['id'],
		$input['message'] ?? '',
		$input['discord'] ?? $user['discord'],
		$input['telegram'] ?? $user['telegram'],
		$input['vk'] ?? $user['vk'],
		$input['relevant_until'] ?? null
	);
	
	if (!empty($_FILES['images'])) {
		$imgService = new ImageService();
		$files = $_FILES['images'];
		$count = min(4, count($files['name']));
		for ($i = 0; $i < $count; $i++) {
			if ($files['error'][$i] === UPLOAD_ERR_OK) {
				$file = [
					'tmp_name' => $files['tmp_name'][$i],
					'size' => $files['size'][$i],
					'error' => $files['error'][$i]
				];
				$path = $imgService->uploadApplicationImage($file, $appId);
				if ($path) {
					$appRepo->addImage($appId, $path, $i);
				}
			}
		}
	}
	
	json(['success' => true, 'id' => $appId]);
} catch (\InvalidArgumentException $e) {
	json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
}

?>