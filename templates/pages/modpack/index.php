<?php
/**
 * @var string $platform
 * @var string $slug
 * @var array|null $modpack
 * @var array|null $user
 */

use React\EventLoop\Loop;
use React\Http\Browser;
use App\Repository\ModpackRepository;
use App\Repository\ApplicationRepository;

$config = require CONFIG_PATH . '/app.php';
$modpackRepo = new ModpackRepository();
$appRepo = new ApplicationRepository();

$modpack = $modpackRepo->findBySlug($platform, $slug);
$isLoading = false;

if (!$modpack) {
    include_once 'api-loading-modpack.php';
}

$currentUserId = $user['id'] ?? null;
$applications = $modpack ? $appRepo->findByModpack($modpack['id'], $currentUserId, 20) : [];
$applicationCount = $modpack ? $appRepo->countByModpack($modpack['id']) : 0;
$userApplication = $modpack && $user ? $appRepo->getUserApplication($modpack['id'], $user['id']) : null;

// Максимальная дата актуальности (1 месяц)
$maxRelevantDate = date('Y-m-d', strtotime('+31 days'));
$defaultRelevantDate = date('Y-m-d', strtotime('+14 days'));
?>

<?php if (!$modpack): ?>
    <div class="alert alert-error">Модпак не найден</div>
<?php else: ?>
    <?php include_once 'modpack-page/modpack-page.php'; ?>
    
    <?php if ($user): ?>
        <?php if ($userApplication): ?>
            <?php 
            // Модалка редактирования существующей заявки
            $application = $userApplication;
            $modalId = 'editMyAppModal';
            $mode = 'edit';
            require TEMPLATES_PATH . '/components/application-modal.php'; 
            ?>
        <?php else: ?>
            <?php 
            // Модалка создания новой заявки
            $application = null;
            $modalId = 'createAppModal';
            $mode = 'create';
            $modpackId = $modpack['id'];
            require TEMPLATES_PATH . '/components/application-modal.php'; 
            ?>
        <?php endif; ?>
        
        <?php include_once 'js-script.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>
