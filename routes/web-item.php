<?php
/**
 * Дополнительные веб роуты для страницы элемента
 * Добавить в routes/web.php
 */

use App\Controllers\Web\ItemController;

// Страница отдельного элемента папки
Router::get('/item/:id', [ItemController::class, 'show'])
    ->where('id', '[0-9]+');
