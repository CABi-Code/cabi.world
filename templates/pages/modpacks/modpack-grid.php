<div data-view="<?= e($view ?? 'grid') ?>">
    <div class="modpack-grid">
        <?php foreach ($modpacks as $mp): ?>
            <?php
            $platform     = $platform ?? 'modrinth';
            $slug         = $mp['slug'] ?? '';
            $title        = $mp['title'] ?? $mp['name'] ?? 'Без названия';
            $author       = $mp['author'] ?? ($mp['authors'][0]['name'] ?? 'Unknown');
            $icon         = $mp['icon_url'] ?? $mp['logo']['thumbnailUrl'] ?? '';
            $downloads    = $mp['downloads'] ?? $mp['downloadCount'] ?? 0;
            $follows      = $mp['follows'] ?? 0;
            $count        = $appCounts[$slug] ?? 0;
            $showFollows  = $showFollows ?? ($platform === 'modrinth');
            ?>

            <div class="mp-card">
                <a href="/modpack/<?= $platform ?>/<?= e($slug) ?>" class="mp-card-link">
                    <img src="<?= e($icon) ?>" alt="" class="mp-card-img"
                         onerror="this.style.display='none'">

                    <div class="mp-card-body">
                        <div class="mp-card-info">
                            <div class="mp-card-title"><?= e($title) ?></div>
                            <div class="mp-card-author"><?= e($author) ?></div>
                        </div>

                        <div class="mp-card-stats">
                            <span>
                                <svg width="12" height="12"><use href="#icon-download"/></svg>
                                <?= formatNumber($downloads) ?>
                            </span>

                            <?php if ($showFollows && $follows > 0): ?>
                                <span>
                                    <svg width="12" height="12"><use href="#icon-heart"/></svg>
                                    <?= formatNumber($follows) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($count > 0): ?>
                                <span class="mp-card-badge"><?= $count ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>