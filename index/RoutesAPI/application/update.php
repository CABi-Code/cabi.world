<?php

use App\Repository\ApplicationRepository;

if (!$user) json(['error' => 'Unauthorized'], 401);
$appId = (int) ($input['id'] ?? 0);
if ($appId <= 0) json(['error' => 'Invalid ID'], 400);

$appRepo = new ApplicationRepository();

$dateValidation = $appRepo->validateRelevantUntil($input['relevant_until'] ?? null);
if (!$dateValidation['valid']) {
	json(['errors' => ['relevant_until' => $dateValidation['error']]], 400);
}

if (empty(trim($input['message'] ?? ''))) {
	json(['errors' => ['message' => 'Введите сообщение']], 400);
}

try {
	$updated = $appRepo->update(
		$appId,
		$user['id'],
		$input['message'] ?? '',
		$input['discord'] ?? null,
		$input['telegram'] ?? null,
		$input['vk'] ?? null,
		$input['relevant_until'] ?? null
	);
	
	if (!$updated) json(['error' => 'Not found or not yours'], 404);
	json(['success' => true]);
} catch (\InvalidArgumentException $e) {
	json(['errors' => ['relevant_until' => $e->getMessage()]], 400);
}

?>