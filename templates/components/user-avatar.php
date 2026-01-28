<?php
/**
 * Компонент отображения аватара пользователя
 * 
 * @var array $avatarUser - пользователь (должен содержать avatar, avatar_bg_value, username)
 * @var string $size - размер: xs, sm, md, lg, xl (по умолчанию md)
 * @var string $class - дополнительные CSS классы
 */

$size = $size ?? 'md';
$class = $class ?? '';

// Размеры в пикселях
$sizes = [
    'xs' => 24,
    'sm' => 32,
    'md' => 40,
    'lg' => 48,
    'xl' => 64,
    'xxl' => 80,
];

$px = $sizes[$size] ?? 40;

// Градиент для аватара без изображения
$avatarColors = explode(',', $avatarUser['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
$gradientStyle = 'background:linear-gradient(135deg,' . ($avatarColors[0] ?? '#3b82f6') . ',' . ($avatarColors[1] ?? $avatarColors[0] ?? '#8b5cf6') . ')';

$initial = mb_strtoupper(mb_substr($avatarUser['username'] ?? 'U', 0, 1));
?>

<div class="user-avatar user-avatar--<?= $size ?> <?= e($class) ?>" 
     style="width:<?= $px ?>px;height:<?= $px ?>px;<?= empty($avatarUser['avatar']) ? $gradientStyle : '' ?>">
    <?php if (!empty($avatarUser['avatar'])): ?>
        <img src="<?= e($avatarUser['avatar']) ?>" alt="<?= e($avatarUser['username'] ?? '') ?>">
    <?php else: ?>
        <span><?= $initial ?></span>
    <?php endif; ?>
</div>
