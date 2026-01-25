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

$modalId = $modalId ?? 'applicationModal';
$mode = $mode ?? (isset($application['id']) && $application['id'] ? 'edit' : 'create');
$isEdit = $mode === 'edit';

$maxRelevantDate = date('Y-m-d', strtotime('+31 days'));
$minRelevantDate = date('Y-m-d');
$defaultRelevantDate = date('Y-m-d', strtotime('+7 days'));

// Значения из заявки или по умолчанию
$appId = $application['id'] ?? '';
$appMessage = $application['message'] ?? '';
$appRelevantUntil = $application['relevant_until'] ?? $defaultRelevantDate;

// Определяем режим контактов
// Если в заявке контакты совпадают с профилем или их нет - режим "default"
$appDiscord = $application['contact_discord'] ?? null;
$appTelegram = $application['contact_telegram'] ?? null;
$appVk = $application['contact_vk'] ?? null;

$userDiscord = $user['discord'] ?? '';
$userTelegram = $user['telegram'] ?? '';
$userVk = $user['vk'] ?? '';

// Определяем, какой режим контактов использовать
$useDefaultContacts = true;
if ($isEdit && $application) {
    // Если хотя бы один контакт отличается от профиля - режим "custom"
    if (($appDiscord !== null && $appDiscord !== $userDiscord) ||
        ($appTelegram !== null && $appTelegram !== $userTelegram) ||
        ($appVk !== null && $appVk !== $userVk)) {
        $useDefaultContacts = false;
    }
}

// Подключаем подшаблоны
include __DIR__ . '/application-modal/modal-wrapper.php';
?>

<script>
<?php include __DIR__ . '/application-modal/modal-script.php'; ?>
</script>
