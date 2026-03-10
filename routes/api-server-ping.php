<?php
/**
 * Дополнительные API роуты для пинга серверов
 * Добавить в routes/api.php
 */

use App\Controllers\Api\ServerPingController;

// Server Ping - публичные (report удалён, данные сохраняются на сервере при пинге)
Router::get('/api/server-ping', [ServerPingController::class, 'ping']);
Router::get('/api/server-ping/history', [ServerPingController::class, 'history']);
