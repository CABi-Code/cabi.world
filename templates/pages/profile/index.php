<?php
/**
 * Страница профиля пользователя
 * 
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 */

use App\Repository\ApplicationRepository;
use App\Core\Role;

$appRepo = new ApplicationRepository();
$applications = $appRepo->findByUser($profileUser['id'], $isOwner, 20);

// Цвета профиля
$bannerBg = $profileUser['banner_bg_value'] ?? '#3b82f6,#8b5cf6';
$avatarBg = $profileUser['avatar_bg_value'] ?? '#3b82f6,#8b5cf6';
$bannerColors = explode(',', $bannerBg);
$avatarColors = explode(',', $avatarBg);
$bannerStyle = $profileUser['banner'] 
    ? "background-image:url(" . e($profileUser['banner']) . ");background-size:cover;background-position:center" 
    : 'background:linear-gradient(135deg,' . $bannerColors[0] . ',' . ($bannerColors[1] ?? $bannerColors[0]) . ')';
$avatarStyle = $profileUser['avatar'] 
    ? "" 
    : 'background:linear-gradient(135deg,' . $avatarColors[0] . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';

// Проверяем доступ к панели управления
$canAccessAdmin = $isOwner && Role::isModerator($user['role'] ?? null);
?>

<?php include_once 'profile-banner.php'; ?>

<?php include_once 'profile-header.php'; ?>

<?php include_once 'profile-section.php'; ?>

<?php if ($isOwner): ?>
    <?php 
    $application = null;
    $modalId = 'editAppModal';
    $mode = 'edit';
    require TEMPLATES_PATH . '/components/application-modal.php'; 
    ?>

    <?php include_once 'js-script.php'; ?>
<?php endif; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>
