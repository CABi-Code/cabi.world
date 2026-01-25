<?php
/**
 * Универсальное модальное окно для создания/редактирования заявки
 * 
 * @var array|null $application - данные заявки для редактирования (null для создания)
 * @var array $user - текущий пользователь
 * @var int|null $modpackId - ID модпака (для создания)
 * @var string $modalId - ID модального окна
 * @var string $mode - 'create' или 'edit'
 */

use App\Repository\ApplicationRepository;

$modalId = $modalId ?? 'applicationModal';
$mode = $mode ?? (isset($application['id']) && $application['id'] ? 'edit' : 'create');
$isEdit = $mode === 'edit';

$maxRelevantDate = date('Y-m-d', strtotime('+' . ApplicationRepository::MAX_RELEVANCE_DAYS . ' days'));
$minRelevantDate = date('Y-m-d');
$defaultRelevantDate = date('Y-m-d', strtotime('+7 days'));

// Значения из заявки или по умолчанию
$appId = $application['id'] ?? '';
$appMessage = $application['message'] ?? '';
$appRelevantUntil = $application['relevant_until'] ?? $defaultRelevantDate;

// Контакты
$appDiscord = $application['contact_discord'] ?? null;
$appTelegram = $application['contact_telegram'] ?? null;
$appVk = $application['contact_vk'] ?? null;

$userDiscord = $user['discord'] ?? '';
$userTelegram = $user['telegram'] ?? '';
$userVk = $user['vk'] ?? '';

// Определяем режим контактов: если все contact_* = null, то режим "по умолчанию"
$useDefaultContacts = true;
if ($isEdit && $application) {
    if ($appDiscord !== null || $appTelegram !== null || $appVk !== null) {
        $useDefaultContacts = false;
    }
}

// Подключаем подшаблоны
include __DIR__ . '/application-modal/modal-wrapper.php';
?>

<script>
<?php include __DIR__ . '/application-modal/modal-script.php'; ?>
</script>
