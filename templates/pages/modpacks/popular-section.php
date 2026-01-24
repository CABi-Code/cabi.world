<?php if ($hasPopular): ?>
<div class="popular-section">
    <div class="popular-header">
        <div>
            <div class="popular-title">🔥 С активными заявками</div>
        </div>
    </div>

    <!-- Desktop Grid -->
    <div class="popular-grid">
        <?php foreach ($popularModpacks as $mp): ?>
            <a href="/modpack/<?= $platform ?>/<?= e($mp['slug']) ?>" class="popular-card">
                <div class="popular-card-icon">
                    <?php if ($mp['icon_url']): ?>
                        <img src="<?= e($mp['icon_url']) ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="popular-card-info">
                    <div class="popular-card-name"><?= e($mp['name']) ?></div>
                    <div class="popular-card-count"><?= $mp['active_app_count'] ?> заявок</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Mobile Carousel -->
    <div class="popular-carousel-wrapper">
        <div class="popular-carousel">
            <?php foreach ($popularModpacks as $mp): ?>
                <a href="/modpack/<?= $platform ?>/<?= e($mp['slug']) ?>" class="popular-card">
                    <div class="popular-card-icon">
                        <?php if ($mp['icon_url']): ?>
                            <img src="<?= e($mp['icon_url']) ?>" alt="">
                        <?php endif; ?>
                    </div>
                    <div class="popular-card-info">
                        <div class="popular-card-name"><?= e($mp['name']) ?></div>
                        <div class="popular-card-count"><?= $mp['active_app_count'] ?> заявок</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>