<?php
/**
 * Главная страница - лента заявок
 * 
 * @var array|null $user - текущий пользователь (передаётся из контроллера)
 */

use App\Repository\ApplicationRepository;
use App\Core\Role;

$appRepo = new ApplicationRepository();
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = $_GET['sort'] ?? 'date';
$limit = 20;
$offset = ($page - 1) * $limit;

$applications = $appRepo->findAllAccepted($limit, $offset, $sort);
$totalCount = $appRepo->countAllAccepted();
$totalPages = max(1, (int)ceil($totalCount / $limit));

// Функция для получения стиля аватара
if (!function_exists('getAvatarStyle')) {
    function getAvatarStyle($app) {
        if (!empty($app['avatar'])) return '';
        $colors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
        return 'background:linear-gradient(135deg,' . $colors[0] . ',' . ($colors[1] ?? $colors[0]) . ')';
    }
}
?>

<div class="hero">
    <h1 class="hero-title">Найди компанию для игры</h1>
    <p class="hero-subtitle">Смотри заявки игроков и находи тиммейтов</p>
</div>

<!-- Форма подачи заявки -->
<?php include_once TEMPLATES_PATH . '/components/application-form/form.php'; ?>

<?php include_once __DIR__ . '/applications/applications.php'; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>
