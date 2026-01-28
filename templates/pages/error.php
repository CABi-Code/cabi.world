<?php
/**
 * Страница ошибки
 * 
 * @var string $title
 * @var string $message
 * @var array|null $user
 */
?>

<div class="error-page">
    <div class="error-content">
        <h1 class="error-title"><?= e($title ?? 'Ошибка') ?></h1>
        <p class="error-message"><?= e($message ?? 'Что-то пошло не так') ?></p>
        <a href="/" class="btn btn-primary">На главную</a>
    </div>
</div>
