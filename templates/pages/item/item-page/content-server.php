<?php
/**
 * content-server.php
 * Контент для элемента типа "server"
 * @var array $item
 * @var array $settings
 */
$ip = $settings['ip'] ?? '';
$port = $settings['port'] ?? 25565;
?>
<div class="item-content item-content-server">
    <?php if ($ip): ?>
        <div class="server-address-card" id="serverAddressCard">
            <div class="address-main">
                <span class="address-ip"><?= e($ip) ?><?= $port !== 25565 ? ':' . $port : '' ?></span>
                <button class="btn btn-primary btn-sm" id="serverCopyBtn" onclick="copyServerIp()">
                    <svg width="14" height="14"><use href="#icon-copy"/></svg>
                    <span>Копировать</span>
                </button>
            </div>
            
            <div class="address-status" id="serverStatus">
                <span class="status-dot checking"></span>
                <span class="status-text">Загрузка...</span>
            </div>

            <div class="stat-card-off">
                <div class="stat-icon"><svg width="20" height="20"><use href="#icon-tag"/></svg></div>
                <div class="stat-value" id="statVersion">-</div>
                <div class="stat-label">Версия</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- MOTD -->
<div class="server-motd-section" id="serverMotdSection" style="display: none;">
    <h2 class="section-title">MOTD</h2>
    <div id="serverMotdWrapper" style="
        width: 100%;
        overflow: hidden;
    ">

	<div id="serverMotd" class="font-minecraft" style="
		opacity: 0;
		transition: opacity 0.3s ease, transform 0.2s ease;
		display: block;
		width: 520px;
		margin: 0 auto;
		height: 48px;
		overflow: hidden;
		text-align: left;
		background: #2b2b2b;
		border: 2px solid #1a1a1a;
		border-radius: 4px;
		padding: 4px 8px;
		font-size: 16px;
		line-height: 18px;
		color: #aaaaaa;
		white-space: pre-wrap;
		overflow-wrap: anywhere;
		image-rendering: pixelated;
		box-shadow: inset 0 0 8px rgba(0,0,0,0.5);
		transform-origin: center center;
		transition: transform 0.2s ease;
	"></div>
    </div>
</div>

<script>
document.fonts.load('1em MinecraftFont').then(function() {
    const motd = document.getElementById('serverMotd');
    if (motd) {
        motd.style.opacity = '1';
        console.log('Шрифт загружен, отображаем MOTD');
    }
});
</script>

    <!-- Игроки -->
    <div class="server-players-section" id="serverPlayersSection" style="display: none;">
<h2 class="section-title" style="display:flex; align-items:center; margin:0; width:fit-content;">
    <span style="">Игроки онлайн</span>
    <svg width="14" height="14" style="color:#808080; margin-left:8px;"><use href="#icon-users"/></svg>
    <span id="statPlayers" style="color:#808080; font-size:14px; margin-left:-5px;">0/0</span>
</h2>

<div class="players-list" id="playersList" style="
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
    align-items: flex-start;
    justify-content: flex-start;
"></div>
    </div>

    <!-- График -->
    <div class="server-chart-section">
        <h2 class="section-title">
            История онлайна
            <select id="chartPeriod" class="chart-period-select" style="color:#808080; font-size:14px;">
                <option value="24">24 часа</option>
                <option value="72">3 дня</option>
                <option value="168">7 дней</option>
            </select>
        </h2>
        <div class="chart-container" id="chartContainer">
            <canvas id="onlineChart"></canvas>
        </div>
        <p style="color:#808080; font-size:12px;">
            * С 12.02.2026 история графика обновляется сервером каждую минуту. <?php // * История обновляется пользователями, находящимися на странице. Без посетителей данные не записываются. ?>
        </p>
    </div>

    <?php if (!empty($settings['query_ip'])): ?>
        <div class="server-query-info">
            <span class="query-label">Query порт:</span>
            <span class="query-value"><?= e($settings['query_ip']) ?>:<?= $settings['query_port'] ?? 25565 ?></span>
        </div>
    <?php endif; ?>
</div>