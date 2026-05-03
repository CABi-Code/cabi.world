<?php
/**
 * JS логика для выделенной страницы сервера
 * @var array $item
 * @var array $settings
 */
$ip = $settings['ip'] ?? '';
$port = $settings['port'] ?? 25565;
$serverId = $settings['server_id'] ?? 0;
?>

<script>
(function() {
    const serverIp = '<?= e($ip) ?>';
    const serverPort = <?= (int)$port ?>;
    const serverItemId = <?= (int)($item['id'] ?? 0) ?>;
    const globalServerId = <?= (int)$serverId ?>;
    let pingInterval = null;
    let chart = null;
    let chartLoadToken = 0;

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

    // ── Пинг через серверный эндпоинт (данные сохраняются автоматически) ──
    async function doPing(ip, port) {
        try {
            const itemParam = serverItemId ? `&item_id=${serverItemId}` : '';
            const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(ip)}&port=${port}&simpl=0${itemParam}`);
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
            return offlineResult();
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

    // ── Главный пинг (данные сохраняются сервером автоматически) ──
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
    const boldSpans = temp.querySelectorAll('span[style*="font-weight: bold"], span[style*="font-weight:bold"]');
    boldSpans.forEach(span => {
        span.classList.add('bold-simulated');
        span.style.fontWeight = '';
    });
    return temp.innerHTML;
}

function processMotdText(el) {
    const directSpans = Array.from(el.children).filter(child => child.tagName === 'SPAN');
    const normalLetterSpacing = '0px';
    const normalWordSpacing   = '4px';
    const boldLetterSpacing   = '2px';
    const boldWordSpacing     = '6px';
    const darkenFactor = 0.25;
    const boldThickenShifts = [{x: 1.90, y: 0}, {x: 2.10, y: 0}, {x: 2.00, y: 0}];
    const boldShadowShifts = [{x: 2, y: 2}, {x: 4, y: 2}];

    el.style.letterSpacing = normalLetterSpacing;
    el.style.wordSpacing   = normalWordSpacing;
    el.style.textShadow    = 'none';

    for (let i = 0; i < directSpans.length; i++) {
        const span = directSpans[i];
        const isBold = span.classList.contains('bold-simulated');
        const computedStyle = getComputedStyle(span);
        const textColor     = computedStyle.color;
        const shadowColor   = darkenColor(textColor, darkenFactor);

        if (isBold) {
            span.style.textShadow = 'none';
            let textContent = span.textContent;
            let trailingSpace = '';
            if (textContent.endsWith(' ')) {
                trailingSpace = ' ';
                textContent = textContent.trimEnd();
                span.textContent = textContent;
            }

            const wrapper = document.createElement('span');
            wrapper.style.cssText = `display:inline-block;position:relative;white-space:nowrap;transform:translateZ(0);backface-visibility:hidden;-webkit-font-smoothing:antialiased;`;
            span.parentNode.insertBefore(wrapper, span);
            wrapper.appendChild(span);
            span.style.position = 'relative';
            span.style.zIndex = '10';
            span.style.color = textColor;
            span.style.letterSpacing = boldLetterSpacing;
            span.style.wordSpacing = boldWordSpacing;

            boldThickenShifts.forEach(shift => {
                const thickenSpan = document.createElement('span');
                thickenSpan.textContent = textContent;
                thickenSpan.style.cssText = `position:absolute;left:${shift.x}px;top:${shift.y}px;color:${textColor};z-index:9;pointer-events:none;letter-spacing:${boldLetterSpacing};word-spacing:${boldWordSpacing};user-select:none;`;
                thickenSpan.setAttribute('aria-hidden', 'true');
                wrapper.appendChild(thickenSpan);
            });
            boldShadowShifts.forEach(shift => {
                const shadowSpan = document.createElement('span');
                shadowSpan.textContent = textContent;
                shadowSpan.style.cssText = `position:absolute;left:${shift.x}px;top:${shift.y}px;color:${shadowColor};z-index:1;pointer-events:none;letter-spacing:${boldLetterSpacing};word-spacing:${boldWordSpacing};user-select:none;`;
                shadowSpan.setAttribute('aria-hidden', 'true');
                wrapper.appendChild(shadowSpan);
            });
            if (trailingSpace) {
                const spaceNode = document.createTextNode(trailingSpace);
                wrapper.parentNode.insertBefore(spaceNode, wrapper.nextSibling);
            }
        } else {
            span.style.textShadow = `2px 2px 0 ${shadowColor}`;
        }
    }
    requestAnimationFrame(() => { el.style.opacity = '1'; scaleMotd(); });
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
    } else { return color; }
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

// ── Обновление UI с favicon ──
function updateServerUI(data) {
    const statusEl = document.getElementById('serverStatus');
    if (!statusEl) return;

    const dot  = statusEl.querySelector('.status-dot');
    const text = statusEl.querySelector('.status-text');

    // Обновление favicon
    if (data.favicon) {
        updateServerFavicon(data.favicon);
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

function updateServerFavicon(faviconData) {
    // Обновляем favicon в блоке адреса
    const el = document.getElementById('serverFavicon');
    if (el && faviconData) {
        el.src = faviconData;
        el.style.display = '';
        el.closest('.server-favicon-wrapper')?.classList.add('has-favicon');
    }
    // Обновляем favicon в заголовке страницы с мини-иконкой
    const headerIcon = document.getElementById('itemHeaderIcon');
    if (headerIcon && faviconData) {
        const existingImg = headerIcon.querySelector('img:first-child');
        if (existingImg) {
            existingImg.src = faviconData;
        } else {
            const color = headerIcon.style.color || '#f59e0b';
            const iconName = headerIcon.dataset.icon || 'server';
            headerIcon.style.cssText = 'position:relative;overflow:visible;';
            headerIcon.innerHTML = `<img src="${faviconData}" width="32" height="32" alt="" style="border-radius:6px;image-rendering:pixelated;">
                <span class="header-favicon-mini" style="color:${color};">
                    <svg width="12" height="12"><use href="#icon-${iconName}"/></svg>
                </span>`;
            headerIcon.classList.remove('item-icon-server-default');
            headerIcon.classList.add('item-icon-favicon');
        }
    }
}

    // ── Превью скина ──
    window.showSkinPreview = function(el) {
        const skinData = el?.dataset?.skin;
        if (!skinData) return;
        document.getElementById('skinPreviewOverlay')?.remove();
        const overlay = document.createElement('div');
        overlay.id = 'skinPreviewOverlay';
        overlay.style.cssText = `position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;cursor:pointer;`;
        overlay.onclick = () => overlay.remove();
        overlay.innerHTML = `
            <div style="background:#1a1a1a;border-radius:12px;padding:20px;text-align:center;max-width:300px;" onclick="event.stopPropagation()">
                <img src="${skinData}" style="image-rendering:pixelated;width:192px;height:auto;border-radius:4px;">
                <div style="color:#888;font-size:12px;margin-top:10px;">Текстура скина · Клик вне окна для закрытия</div>
            </div>`;
        document.body.appendChild(overlay);
    };

    // ── Автопинг ──
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
        if (pingInterval) { clearTimeout(pingInterval); pingInterval = null; }
    }

    // ── Улучшенный график ──
    async function loadChart(hours) {
        const token = ++chartLoadToken;
        try {
            const params = new URLSearchParams();
            if (globalServerId) params.set('server_id', globalServerId);
            else params.set('item_id', serverItemId);
            params.set('hours', hours);
            const res = await fetch(`/api/server-ping/history?${params}`);
            const data = await res.json();
            // Игнорируем устаревшие ответы при быстром переключении периода
            if (token !== chartLoadToken) return;
            renderChart(data.history || [], hours);
        } catch (e) {
            if (token !== chartLoadToken) return;
            console.error('Failed to load chart:', e);
            renderChart([], hours);
        }
    }

    function renderChart(history, hours) {
        const container = document.getElementById('chartContainer');
        if (!container) return;
        if (chart) { chart.destroy(); chart = null; }

        if (history.length === 0) {
            container.innerHTML = '<div class="chart-no-data" style="text-align:center;color:#666;padding:40px 0;">Нет данных за этот период</div>';
            return;
        }

        // Полная пересборка контейнера: убирает возможный «Нет данных» и старый canvas
        container.innerHTML = '<canvas id="onlineChart"></canvas>';
        const ctx = document.getElementById('onlineChart');
        if (!ctx) return;

        const labels = history.map(h => {
            const t = h.time || '';
            if (hours === 0 || hours > 168) return t.length >= 10 ? t.substring(5, 10) : t;
            if (hours > 24) return t.length >= 16 ? t.substring(5, 16) : t;
            return t.length >= 16 ? t.substring(11, 16) : t;
        });
        const players = history.map(h => h.players ?? 0);
        const onlineStatus = history.map(h => h.online);
        const maxPlayers = Math.max(...players, 1);

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Игроков онлайн',
                    data: players,
                    borderColor: '#3b82f6',
                    backgroundColor: function(context) {
                        const c = context.chart;
                        const {ctx: cx, chartArea} = c;
                        if (!chartArea) return 'rgba(59,130,246,0.1)';
                        const g = cx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                        g.addColorStop(0, 'rgba(59,130,246,0.02)');
                        g.addColorStop(1, 'rgba(59,130,246,0.2)');
                        return g;
                    },
                    fill: true,
                    tension: 0.4,
                    pointRadius: history.length > 100 ? 0 : 2,
                    pointHoverRadius: 4,
                    pointBackgroundColor: onlineStatus.map(o => o ? '#3b82f6' : '#ef4444'),
                    borderWidth: 2,
                    segment: {
                        borderColor: function(ctx) {
                            return onlineStatus[ctx.p1DataIndex] ? '#3b82f6' : '#ef4444';
                        }
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#ddd',
                        borderColor: 'rgba(59,130,246,0.3)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            label: function(item) {
                                const status = onlineStatus[item.dataIndex] ? 'Онлайн' : 'Офлайн';
                                return `${status} · ${item.raw} игроков`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: maxPlayers <= 10 ? 1 : undefined,
                            color: '#666',
                            font: { size: 11 }
                        },
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: hours > 168 || hours === 0 ? 10 : 20,
                            color: '#666',
                            font: { size: 10 },
                            maxRotation: 45,
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

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

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') stopPinging();
        else if (document.visibilityState === 'visible' && serverIp) startPinging();
    });

    window.addEventListener('resize', scaleMotd);

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
