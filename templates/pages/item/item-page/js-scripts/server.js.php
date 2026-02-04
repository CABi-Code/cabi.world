<?php
/**
 * JS логика для страницы сервера
 * @var array $item
 * @var array $settings
 */
$ip = $settings['ip'] ?? '';
$port = $settings['port'] ?? 25565;
?>

const serverIp = '<?= e($ip) ?>';
const serverPort = <?= (int)$port ?>;
let pingInterval = null;
let chart = null;

// Копирование IP
function copyServerIp() {
    const address = serverPort !== 25565 ? `${serverIp}:${serverPort}` : serverIp;
    navigator.clipboard.writeText(address).then(() => {
        const btn = document.getElementById('serverCopyBtn');
        btn.classList.add('copied');
        btn.innerHTML = '<svg width="14" height="14"><use href="#icon-check"/></svg><span>Скопировано!</span>';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<svg width="14" height="14"><use href="#icon-copy"/></svg><span>Копировать</span>';
        }, 2000);
    });
}

// Пинг сервера
async function pingServer() {
    try {
        const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(serverIp)}&port=${serverPort}`);
        const data = await res.json();
        updateServerUI(data);
        reportStatus(data);
        return data.online;
    } catch (e) {
        updateServerUI({ online: false });
        return false;
    }
}

function updateServerUI(data) {
    const statusEl = document.getElementById('serverStatus');
    const statsEl = document.getElementById('serverStats');
    const playersEl = document.getElementById('serverPlayersSection');
    
    const dot = statusEl.querySelector('.status-dot');
    const text = statusEl.querySelector('.status-text');
    
    if (data.online) {
        dot.className = 'status-dot online pulsing';
        text.textContent = `Онлайн · ${data.players?.online || 0}/${data.players?.max || 0}`;
        
        statsEl.style.display = 'grid';
        document.getElementById('statPlayers').textContent = 
            `${data.players?.online || 0}/${data.players?.max || 0}`;
        document.getElementById('statVersion').textContent = data.version || '-';
        
        // Список игроков
        if (data.players?.sample?.length > 0) {
            playersEl.style.display = 'block';
            document.getElementById('playersList').innerHTML = data.players.sample
                .map(p => `<span class="player-tag">${escapeHtml(p.name)}</span>`)
                .join('');
        } else {
            playersEl.style.display = 'none';
        }
    } else {
        dot.className = 'status-dot offline';
        text.textContent = 'Офлайн';
        statsEl.style.display = 'none';
        playersEl.style.display = 'none';
    }
}

async function reportStatus(data) {
    if (!csrf) return;
    try {
        await fetch('/api/server-ping/report', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                item_id: itemId,
                online: data.online,
                players_online: data.players?.online || 0,
                players_max: data.players?.max || 0,
                players_sample: data.players?.sample || [],
                version: data.version || null
            })
        });
    } catch (e) {}
}

// Автопинг
function startPinging() {
    pingServer().then(online => scheduleNext(online));
}

function scheduleNext(isOnline) {
    const delay = isOnline 
        ? 5000 + Math.random() * 10000 
        : 50000 + Math.random() * 10000;
    pingInterval = setTimeout(() => {
        pingServer().then(online => scheduleNext(online));
    }, delay);
}

// График
async function loadChart(hours = 24) {
    try {
        const res = await fetch(`/api/server-ping/history?item_id=${itemId}&hours=${hours}`);
        const data = await res.json();
        renderChart(data.history || []);
    } catch (e) {
        console.error('Failed to load chart:', e);
    }
}

function renderChart(history) {
    const ctx = document.getElementById('onlineChart');
    if (!ctx) return;
    
    if (chart) chart.destroy();
    
    const labels = history.map(h => h.time.substring(11, 16)); // HH:mm
    const players = history.map(h => h.players);
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Игроков онлайн',
                data: players,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}

// Утилиты
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    if (serverIp) {
        startPinging();
        loadChart(24);
        
        document.getElementById('chartPeriod')?.addEventListener('change', (e) => {
            loadChart(parseInt(e.target.value));
        });
    }
});
