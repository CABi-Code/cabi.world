<?php
use App\Repository\StatsRepository;

$statsRepo = new StatsRepository();
$stats = $statsRepo->getAll();
?>
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-stats">
            <div class="footer-stat">
                <div class="footer-stat-value"><?= number_format($stats['users_count'] ?? 0) ?></div>
                <div class="footer-stat-label">Пользователей</div>
            </div>
            <div class="footer-stat">
                <div class="footer-stat-value"><?= number_format($stats['modpacks_count'] ?? 0) ?></div>
                <div class="footer-stat-label">Модпаков</div>
            </div>
            <div class="footer-stat">
                <div class="footer-stat-value"><?= number_format($stats['applications_count'] ?? 0) ?></div>
                <div class="footer-stat-label">Заявок</div>
            </div>
        </div>
        <div class="footer-copy">
            &copy; <?= date('Y') ?> cabi.world
        </div>
    </div>
</footer>
