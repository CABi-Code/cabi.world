<?php
/**
 * Подключение JS скриптов для вкладки "Моя папка"
 */

// Конфигурация и утилиты
require __DIR__ . '/js/user-folder-config.js.php';
require __DIR__ . '/js/user-folder-utils.js.php';
include __DIR__ . '/js/server-pinger.js.php';

// Модульные панели
require __DIR__ . '/js/user-folder-panels/panel-base.js.php';
require __DIR__ . '/js/user-folder-panels/panel-folder.js.php';
require __DIR__ . '/js/user-folder-panels/panel-chat.js.php';
require __DIR__ . '/js/user-folder-panels/panel-server.js.php';
require __DIR__ . '/js/user-folder-panels/panel-modpack.js.php';
require __DIR__ . '/js/user-folder-panels/panel-mod.js.php';
require __DIR__ . '/js/user-folder-panels/panel-application.js.php';
require __DIR__ . '/js/user-folder-panels/panel-shortcut.js.php';

// Главный контроллер панелей
require __DIR__ . '/js/user-folder-panel.js.php';

// Модальные окна
require __DIR__ . '/js/user-folder-modals.js.php';

// Drag & Drop
require __DIR__ . '/js/user-folder-dragdrop.js.php';

// Основная инициализация
require __DIR__ . '/js/user-folder-main.js.php';

// Пинг серверов в дереве
require __DIR__ . '/js/user-folder-server-ping.js.php';

?>
