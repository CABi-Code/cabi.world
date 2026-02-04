<?php
/**
 * Хлебные крошки на странице элемента
 * @var array $breadcrumbs
 */
?>
<nav class="item-breadcrumbs">
    <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?>
            <span class="breadcrumb-sep">/</span>
        <?php endif; ?>
        
        <?php if ($crumb['url']): ?>
            <a href="<?= e($crumb['url']) ?>" class="breadcrumb-link"><?= e($crumb['name']) ?></a>
        <?php else: ?>
            <span class="breadcrumb-current"><?= e($crumb['name']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
