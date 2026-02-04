<?php
/**
 * Контент для элемента типа "server"
 * @var array $item
 * @var array $settings
 */

$ip = $settings['ip'] ?? '';
$port = $settings['port'] ?? 25565;
$status = $settings['status'] ?? null;
?>
<div class="item-content item-content-server">
    <!-- Блок IP адреса -->
    <?php if ($ip): ?>
        <div class="server-address-card" id="serverAddressCard" data-ip="<?= e($ip) ?>" data-port="<?= $port ?>">
            <div class="address-main">
                <span class="address-ip"><?= e($ip) ?><?= $port !== 25565 ? ':' . $port : '' ?></span>
                <button class="btn btn-primary btn-sm" id="serverCopyBtn" onclick="copyServerIp()">
                    <svg width="14" height="14"><use href="#icon-copy"/></svg>
                    <span>Копировать</span>
                </button>
            </div>
            
            <div class="address-status" id="serverStatus">
                <span class="status-dot checking"></span>
                <span class="status-text">Проверка...</span>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div class="server-stats" id="serverStats" style="display: none;">
        <div class="stat-card">
            <div class="stat-icon"><svg width="20" height="20"><use href="#icon-users"/></svg></div>
            <div class="stat-value" id="statPlayers">0/0</div>
            <div class="stat-label">Игроков</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><svg width="20" height="20"><use href="#icon-activity"/></svg></div>
            <div class="stat-value" id="statUptime">-</div>
            <div class="stat-label">Аптайм</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><svg width="20" height="20"><use href="#icon-tag"/></svg></div>
            <div class="stat-value" id="statVersion">-</div>
            <div class="stat-label">Версия</div>
        </div>
    </div>
    
    <!-- Список игроков -->
    <div class="server-players-section" id="serverPlayersSection" style="display: none;">
        <h2 class="section-title">Игроки онлайн</h2>
        <div class="players-list" id="playersList"></div>
    </div>
    
    <!-- График онлайна -->
    <div class="server-chart-section">
        <h2 class="section-title">
            История онлайна
            <select id="chartPeriod" class="chart-period-select">
                <option value="24">24 часа</option>
                <option value="72">3 дня</option>
                <option value="168">7 дней</option>
            </select>
        </h2>
        <div class="chart-container" id="chartContainer">
            <canvas id="onlineChart"></canvas>
        </div>
    </div>
    
    <!-- Query настройки -->
    <?php if (!empty($settings['query_ip'])): ?>
        <div class="server-query-info">
            <span class="query-label">Query порт:</span>
            <span class="query-value"><?= e($settings['query_ip']) ?>:<?= $settings['query_port'] ?? 25565 ?></span>
        </div>
    <?php endif; ?>
</div>
