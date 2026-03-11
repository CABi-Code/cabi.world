<?php
/**
 * JS логика для выделенной страницы сервера
 * @var array $item
 * @var array $settings
 */
$ip = $settings['ip'] ?? '';
$port = $settings['port'] ?? 25565;
?>

<script>
(function() {
    const serverIp = '<?= e($ip) ?>';
    const serverPort = <?= (int)$port ?>;
    const serverItemId = <?= (int)($item['id'] ?? 0) ?>;
    let pingInterval = null;
    let chart = null;

    // ── Загрузка Minecraft-шрифта ──
    if (!document.getElementById('mcFontStyle')) {
        const style = document.createElement('style');
        style.id = 'mcFontStyle';
        style.textContent = `
            @font-face {
                font-family: 'MinecraftFont';
                src: url('https://cdn.jsdelivr.net/gh/IdreesInc/Monocraft@main/dist/Monocraft.woff2') format('woff2');
                font-display: swap;
            }
        `;
        document.head.appendChild(style);
    }

    // ── Пинг через наш бэкенд (он сам делает fallback на mcsrvstat) ──
    async function doPing(ip, port) {
        try {
            const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(ip)}&port=${port}&simpl=0`);
            if (!res.ok) throw new Error('Backend error');
            const data = await res.json();
            return {
                online:   data.online === true,
                players:  {
                    online: data.players?.online || 0,
                    max:    data.players?.max || 0,
                    list:   data.players?.list || data.players?.sample || []
                },
                version:  data.version || null,
                motd:     {
                    raw:   data.motd?.raw || [],
                    clean: data.motd?.clean || [],
                    html:  data.motd?.html || []
                },
                favicon:  data.favicon || null,
                latency:  data.latency || null,
                source:   data.source || 'backend'
            };
        } catch (e) {
            // Фоллбэк на mcsrvstat только если бэкенд полностью недоступен
            try {
                const addr = port !== 25565 ? `${ip}:${port}` : ip;
                const res = await fetch(`https://api.mcsrvstat.us/3/${encodeURIComponent(addr)}`);
                if (!res.ok) throw new Error('mcsrvstat error');
                const raw = await res.json();
                if (!raw.online) return offlineResult();
                return {
                    online: true,
                    players: {
                        online: raw.players?.online || 0,
                        max:    raw.players?.max || 0,
                        list:   (raw.players?.list || []).map(p =>
                            typeof p === 'string' ? { name: p } : p
                        )
                    },
                    version:  raw.version || null,
                    motd:     {
                        raw:   raw.motd?.raw || [],
                        clean: raw.motd?.clean || [],
                        html:  raw.motd?.html || []
                    },
                    favicon:  raw.icon || null,
                    latency:  null,
                    source:   'mcsrvstat-direct'
                };
            } catch {
                return offlineResult();
            }
        }
    }

    function offlineResult() {
        return {
            online: false,
            players: { online: 0, max: 0, list: [] },
            version: null,
            motd: { raw: [], clean: [], html: [] },
            favicon: null, latency: null
        };
    }

    // ── Главный пинг ──
    async function pingServer() {
        try {
            const data = await doPing(serverIp, serverPort);
            updateServerUI(data);
            return data.online;
        } catch {
            updateServerUI(offlineResult());
            return false;
        }
    }
	
    // ── Игроки с сортировкой и скинами ──
    function renderPlayersList(players) {
        const section   = document.getElementById('serverPlayersSection');
        const container = document.getElementById('playersList');
        if (!section || !container) return;

        let list = (players || [])
            .map(p => typeof p === 'string'
                ? { name: p.trim(), uuid: null, head: null, skin: null, source: null }
                : {
                    name:   (p.name || '').trim(),
                    uuid:   (p.uuid || p.id || '').replace(/-/g, '') || null,
                    head:   p.head || null,
                    skin:   p.skin || null,
                    source: p.source || null
                })
            .filter(p => p.name.length > 0)
            .sort((a, b) => a.name.localeCompare(b.name, undefined, { sensitivity: 'base' }));

        if (list.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';

        container.innerHTML = list.map(p => {
            const headSrc = p.head
                || (p.uuid ? `https://mc-heads.net/avatar/${p.uuid}/32` : null)
                || 'https://mc-heads.net/avatar/MHF_Steve/32';

            const skinAttr = p.skin ? `data-skin="${escHtml(p.skin)}"` : '';
            const clickAttr = p.skin ? 'onclick="showSkinPreview(this)"' : '';

            return `<span class="player-tag" ${skinAttr}
                style="
                    display:inline-flex; align-items:center; gap:6px;
                    padding:4px 10px 4px 6px;
                    background:rgba(255,255,255,0.06);
                    border-radius:6px; font-size:13px;
                    cursor:${p.skin ? 'pointer' : 'default'};
                    transition:background 0.15s;
                "
                onmouseenter="this.style.background='rgba(255,255,255,0.12)'"
                onmouseleave="this.style.background='rgba(255,255,255,0.06)'"
                ${clickAttr}
            >
                <img src="${headSrc}" alt="${escHtml(p.name)}"
                     width="24" height="24" loading="lazy"
                     style="border-radius:3px; image-rendering:pixelated;"
                     onerror="this.src='https://mc-heads.net/avatar/MHF_Steve/32'">
                ${escHtml(p.name)}
                ${p.source && p.source !== 'mojang'
                    ? `<span style="font-size:9px;color:#666;margin-left:2px;">${escHtml(p.source)}</span>`
                    : ''}
            </span>`;
        }).join('');
    }




// ── MOTD с масштабированием ──
function renderMotd(motd) {
    const section = document.getElementById('serverMotdSection');
    const el      = document.getElementById('serverMotd');
    const wrapper = document.getElementById('serverMotdWrapper');
    if (!section || !el || !wrapper) return;

    const html = motd?.html || [];
    if (html.length > 0) {
        const processedHtml = html.map(processLine);
        el.innerHTML = processedHtml.join('\n');
        el.style.opacity = '0';
        section.style.display = '';
        requestAnimationFrame(() => {
            processMotdText(el);
        });
    } else {
        section.style.display = 'none';
    }
}

function processLine(line) {
    const temp = document.createElement('div');
    temp.innerHTML = line;

    // Находим все span с font-weight: bold
    const boldSpans = temp.querySelectorAll('span[style*="font-weight: bold"], span[style*="font-weight:bold"]');

    // Обрабатываем все bold-спаны
    boldSpans.forEach(span => {
        span.classList.add('bold-simulated');
        span.style.fontWeight = ''; // Убираем настоящий bold
    });

    return temp.innerHTML;
}

function processMotdText(el) {
    // Берём все прямые дочерние span'ы
    const directSpans = Array.from(el.children).filter(child => child.tagName === 'SPAN');
    
    // === НАСТРОЙКИ ===
    const DEBUG_SHOW_SPACES = false;
    
    const normalLetterSpacing = '0px';
    const normalWordSpacing   = '4px';
    
    const boldLetterSpacing   = '2px';
    const boldWordSpacing     = '6px';
    
    const darkenFactor = 0.25;
    
    const boldThickenShifts = [
        {x: 1.90, y: 0},
        {x: 2.10, y: 0},
        {x: 2.00, y: 0},
    ];
    
    const boldShadowShifts = [
        {x: 2, y: 2},
        {x: 4, y: 2},
    ];
    // =================

    // Базовые стили
    el.style.letterSpacing = normalLetterSpacing;
    el.style.wordSpacing   = normalWordSpacing;
    el.style.textShadow    = 'none';

    for (let i = 0; i < directSpans.length; i++) {
        const span = directSpans[i];
        
        // Проверяем, является ли сам span жирным
        const isBold = span.classList.contains('bold-simulated');
        
        const computedStyle = getComputedStyle(span);
        const textColor     = computedStyle.color;
        const shadowColor   = darkenColor(textColor, darkenFactor);

        if (isBold) {
            span.style.textShadow = 'none';
            
            let textContent = span.textContent;
            let trailingSpace = '';
            
            // Проверяем, есть ли trailing пробел
            if (textContent.endsWith(' ')) {
                trailingSpace = ' ';
                textContent = textContent.trimEnd();
                span.textContent = textContent; // Удаляем пробел из span
            }
            
            // DEBUG: Визуализация пробелов
            if (DEBUG_SHOW_SPACES) {
                span.textContent = span.textContent.replace(/ /g, '·');
                textContent = textContent.replace(/ /g, '·');
            }
            
            const wrapper = document.createElement('span');
            wrapper.style.cssText = `
                display: inline-block;
                position: relative;
                white-space: nowrap;
                transform: translateZ(0);
                backface-visibility: hidden;
                -webkit-font-smoothing: antialiased;
            `;

            span.parentNode.insertBefore(wrapper, span);
            wrapper.appendChild(span);

            span.style.position = 'relative';
            span.style.zIndex   = '10';
            span.style.color    = textColor;
            span.style.letterSpacing = boldLetterSpacing;
            span.style.wordSpacing   = boldWordSpacing;

            // Утолщение текста
            boldThickenShifts.forEach(shift => {
                const thickenSpan = document.createElement('span');
                thickenSpan.textContent = textContent;
                thickenSpan.style.cssText = `
                    position: absolute;
                    left: ${shift.x}px;
                    top: ${shift.y}px;
                    color: ${textColor};
                    z-index: 9;
                    pointer-events: none;
                    letter-spacing: ${boldLetterSpacing};
                    word-spacing: ${boldWordSpacing};
                    user-select: none;
                `;
                thickenSpan.setAttribute('aria-hidden', 'true');
                wrapper.appendChild(thickenSpan);
            });

            // Тени
            boldShadowShifts.forEach(shift => {
                const shadowSpan = document.createElement('span');
                shadowSpan.textContent = textContent;
                shadowSpan.style.cssText = `
                    position: absolute;
                    left: ${shift.x}px;
                    top: ${shift.y}px;
                    color: ${shadowColor};
                    z-index: 1;
                    pointer-events: none;
                    letter-spacing: ${boldLetterSpacing};
                    word-spacing: ${boldWordSpacing};
                    user-select: none;
                `;
                shadowSpan.setAttribute('aria-hidden', 'true');
                wrapper.appendChild(shadowSpan);
            });

            // Если был trailing пробел, добавляем его ПОСЛЕ wrapper
            if (trailingSpace) {
                const spaceNode = document.createTextNode(trailingSpace);
                wrapper.parentNode.insertBefore(spaceNode, wrapper.nextSibling);
            }

        } else {
            // DEBUG: Визуализация пробелов
            if (DEBUG_SHOW_SPACES) {
                span.textContent = span.textContent.replace(/ /g, '·');
            }
            
            span.style.textShadow = `2px 2px 0 ${shadowColor}`;
        }
    }

    requestAnimationFrame(() => {
        el.style.opacity = '1';
        scaleMotd();
    });
}

function darkenColor(color, factor = 0.25) {
    let r, g, b;
    
    if (color.startsWith('rgb')) {
        const matches = color.match(/\d+/g);
        [r, g, b] = matches.map(v => parseInt(v));
    } else if (color.startsWith('#')) {
        r = parseInt(color.slice(1, 3), 16);
        g = parseInt(color.slice(3, 5), 16);
        b = parseInt(color.slice(5, 7), 16);
    } else {
        return color;
    }
    
    return `rgb(${Math.floor(r * factor)}, ${Math.floor(g * factor)}, ${Math.floor(b * factor)})`;
}

function scaleMotd() {
    const wrapper = document.getElementById('serverMotdWrapper');
    const motd    = document.getElementById('serverMotd');
    if (!wrapper || !motd) return;

    motd.style.transform = 'none';
    wrapper.style.height = 'auto';

    requestAnimationFrame(() => {
        const cw = wrapper.clientWidth;
        const actualW = motd.offsetWidth;
        const actualH = motd.offsetHeight;

        if (actualW > cw) {
            const scale = cw / actualW;
            motd.style.transform = `scale(${scale})`;
            wrapper.style.height = `${Math.ceil(actualH * scale)}px`;
        } else {
            motd.style.transform = 'none';
            wrapper.style.height = 'auto';
        }
    });
}

window.addEventListener('resize', scaleMotd);

function updateServerUI(data) {
    const statusEl = document.getElementById('serverStatus');
    if (!statusEl) return;

    const dot  = statusEl.querySelector('.status-dot');
    const text = statusEl.querySelector('.status-text');

    // Обновляем favicon в заголовке и в блоке адреса
    if (data.favicon) {
        const serverFavicon = document.getElementById('serverFavicon');
        if (serverFavicon) {
            serverFavicon.src = data.favicon;
            serverFavicon.style.display = '';
            serverFavicon.closest('.server-favicon-wrapper')?.classList.add('has-favicon');
        }
        const headerIcon = document.getElementById('itemHeaderIcon');
        if (headerIcon) {
            const existingImg = headerIcon.querySelector('img');
            if (existingImg) {
                existingImg.src = data.favicon;
            } else {
                headerIcon.innerHTML = `<img src="${data.favicon}" width="32" height="32" alt="" style="border-radius:6px;image-rendering:pixelated;">`;
                headerIcon.classList.remove('item-icon-server-default');
                headerIcon.classList.add('item-icon-favicon');
            }
        }
    }

    if (data.online) {
        dot.className = 'status-dot online pulsing';
        text.textContent = `Онлайн · ${data.players.online}/${data.players.max}`;

        const verEl = document.getElementById('statVersion');
        if (verEl) verEl.textContent = data.version || '-';

        const countEl = document.getElementById('statPlayers');
        if (countEl) countEl.textContent = `${data.players.online}/${data.players.max}`;

        renderPlayersList(data.players?.list || []);
        renderMotd(data.motd);
    } else {
        dot.className = 'status-dot offline';
        text.textContent = 'Офлайн';

        ['serverPlayersSection', 'serverMotdSection'].forEach(id => {
            const section = document.getElementById(id);
            if (section) section.style.display = 'none';
        });
    }
}


//===================================================================

    // ── Превью скина ──
    window.showSkinPreview = function(el) {
        const skinData = el?.dataset?.skin;
        if (!skinData) return;

        document.getElementById('skinPreviewOverlay')?.remove();

        const overlay = document.createElement('div');
        overlay.id = 'skinPreviewOverlay';
        overlay.style.cssText = `
            position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,0.7);
            display:flex; align-items:center; justify-content:center;
            cursor:pointer;
        `;
        overlay.onclick = () => overlay.remove();
        overlay.innerHTML = `
            <div style="background:#1a1a1a; border-radius:12px; padding:20px;
                text-align:center; max-width:300px;" onclick="event.stopPropagation()">
                <img src="${skinData}"
                     style="image-rendering:pixelated; width:192px; height:auto; border-radius:4px;">
                <div style="color:#888; font-size:12px; margin-top:10px;">
                    Текстура скина · Клик вне окна для закрытия
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    };

    // Репорт удалён — сервер сохраняет данные автоматически при пинге

    // ── Автопинг с адаптивным интервалом ──
    function startPinging() {
        pingServer().then(online => scheduleNext(online));
    }

    function scheduleNext(isOnline) {
        if (pingInterval) clearTimeout(pingInterval);
        const delay = isOnline
            ? 7000 + Math.random() * 10000
            : 50000 + Math.random() * 10000;
        pingInterval = setTimeout(() => {
            pingServer().then(online => scheduleNext(online));
        }, delay);
    }

    function stopPinging() {
        if (pingInterval) {
            clearTimeout(pingInterval);
            pingInterval = null;
        }
    }

    // ── График ──
    async function loadChart(hours) {
        try {
            const res = await fetch(`/api/server-ping/history?item_id=${serverItemId}&hours=${hours}`);
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

        const labels  = history.map(h => {
            const t = h.time || '';
            return t.length >= 16 ? t.substring(11, 16) : t;
        });
        const players = history.map(h => h.players ?? h.players_online ?? 0);

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
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { maxTicksLimit: 20 } }
                }
            }
        });
    }

    // ── Утилиты ──
    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    // Глобальная функция для onclick в HTML
    window.copyServerIp = function() {
        const address = serverPort !== 25565 ? `${serverIp}:${serverPort}` : serverIp;
        navigator.clipboard.writeText(address).then(() => {
            const btn = document.getElementById('serverCopyBtn');
            if (!btn) return;
            btn.classList.add('copied');
            btn.innerHTML = '<svg width="14" height="14"><use href="#icon-check"/></svg><span>Скопировано!</span>';
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = '<svg width="14" height="14"><use href="#icon-copy"/></svg><span>Копировать</span>';
            }, 2000);
        });
    };

    // ── Visibility control (автономный) ──
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            stopPinging();
        } else if (document.visibilityState === 'visible' && serverIp) {
            startPinging();
        }
    });

    // Ресайз → пересчёт MOTD
    window.addEventListener('resize', scaleMotd);

    // ── Init ──
    document.addEventListener('DOMContentLoaded', () => {
        if (serverIp) {
            startPinging();
            loadChart(24);
            document.getElementById('chartPeriod')?.addEventListener('change', e => {
                loadChart(parseInt(e.target.value));
            });
        }
    });
})();
</script>