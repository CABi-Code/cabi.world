<?php
/**
 * Дополнительные API роуты для пинга серверов
 * Добавить в routes/api.php
 */

use App\Controllers\Api\ServerPingController;

// Server Ping - публичные
Router::get('/api/server-ping', [ServerPingController::class, 'ping']);
Router::get('/api/server-ping/history', [ServerPingController::class, 'history']);

// Server Ping - отправка отчета (с CSRF)
Router::post('/api/server-ping/report', [ServerPingController::class, 'report'])
    ->middleware('csrf');
