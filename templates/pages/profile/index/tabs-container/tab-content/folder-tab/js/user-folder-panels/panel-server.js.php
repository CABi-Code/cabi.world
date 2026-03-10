<script>
window.PanelServer = {
    pingInterval: null,
    currentServerId: null,

    render(item, path, children) {
        this.currentServerId = item.id;
        const settings = typeof item.settings === 'string'
            ? JSON.parse(item.settings) : (item.settings || {});

        let html = PanelBase.getHeader();
        html += PanelBase.getPath(path);
        html += PanelBase.getItemHeader(item);
        html += PanelBase.getDescription(item);

        if (settings.ip) {
            const port = settings.port && settings.port !== 25565 ? ':' + settings.port : '';

            // Favicon + мини-иконка
            html += `<div id="panelFaviconWrapper" style="display:none;margin:8px 0;">
                <div style="position:relative;width:48px;height:48px;flex-shrink:0;">
                    <img id="panelServerFavicon" src="" alt="Server icon"
                         style="width:48px;height:48px;border-radius:8px;image-rendering:pixelated;object-fit:cover;">
                    <span style="position:absolute;bottom:-4px;left:-4px;width:22px;height:22px;border-radius:6px;
                                 background:var(--bg-primary,#1a1a2e);display:flex;align-items:center;justify-content:center;
                                 box-shadow:0 0 0 2px rgba(0,0,0,0.4);z-index:2;color:${item.color || '#f59e0b'};">
                        <svg width="14" height="14"><use href="#icon-${item.icon || 'server'}"/></svg>
                    </span>
                </div>
            </div>`;

            html += `<div class="panel-server-info" id="serverInfoBlock"
                data-ip="${esc(settings.ip)}"
                data-port="${settings.port || 25565}"
                onclick="PanelServer.copyIp(this)">
                <div class="server-address">
                    <span class="server-ip">${esc(settings.ip)}${port}</span>
                </div>
                <button class="btn btn-ghost btn-xs copy-btn" id="copyIpBtn">
                    <svg width="14" height="14"><use href="#icon-copy"/></svg>
                    <span>Копировать</span>
                </button>
            </div>`;

            if (settings.query_ip) {
                html += `<div class="panel-server-query">
                    <span class="query-label">Query:</span>
                    <span class="query-value">${esc(settings.query_ip)}:${settings.query_port || 25565}</span>
                </div>`;
            }
        }

        html += `<div class="panel-server-stats" id="serverStats" style="display:none;">
            <div class="server-status" id="serverStatus">
                <span class="status-indicator checking"></span>
                <span class="status-text">Проверка...</span>
            </div>
            <div class="server-version" id="serverVersion"></div>
        </div>`;

        // MOTD
        html += `<div id="panelMotdSection" style="display:none; margin:8px 0;">
            <div id="panelMotdWrapper" style="width:100%; overflow:hidden;">
                <div id="panelMotd" style="
                    width:520px;
                    height:44px;
                    background:#2b2b2b;
                    border:2px solid #1a1a1a;
                    border-radius:4px;
                    padding:4px 8px;
                    font-family:'MinecraftFont','Courier New',monospace;
                    font-size:20px;
                    line-height:22px;
                    color:#aaa;
                    white-space:pre;
                    overflow:hidden;
                    text-shadow:2px 2px 0 #3f3f3f;
                    image-rendering:pixelated;
                    box-shadow:inset 0 0 8px rgba(0,0,0,0.5);
                    transform-origin:top left;
                "></div>
            </div>
        </div>`;

        // Игроки
        html += `<div class="panel-server-players" id="serverPlayersList" style="display:none;">
            <div class="server-players" style="display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16"><use href="#icon-users"/></svg>
                <div class="panel-children-title">Игроки онлайн:</div>
            </div>
            <div class="players-list" id="playersListContainer" style="
                display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;
            "></div>
        </div>`;

        return html;
    },

    afterRender(item) {
        this.setCurrentItem(item);
        const settings = typeof item.settings === 'string'
            ? JSON.parse(item.settings) : (item.settings || {});

        if (settings.ip) {
            this.startPinging(settings.ip, settings.port || 25565, item.id);
        }
    },

    copyIp(el) {
        const ip = el.dataset.ip;
        const port = el.dataset.port;
        const full = port && port !== '25565' ? `${ip}:${port}` : ip;

        navigator.clipboard.writeText(full).then(() => {
            const btn = document.getElementById('copyIpBtn');
            if (!btn) return;
            btn.classList.add('copied');
            btn.innerHTML = `<svg width="14" height="14"><use href="#icon-check"/></svg><span>Скопировано!</span>`;
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = `<svg width="14" height="14"><use href="#icon-copy"/></svg><span>Копировать</span>`;
            }, 2000);
        });
    },

    // ── Пинг через общий ServerPinger ──
    async _ping(ip, port) {
        if (typeof ServerPinger !== 'undefined') {
            return ServerPinger.ping(ip, port);
        }
        // Фоллбэк если ServerPinger не загружен
        try {
            const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(ip)}&port=${port}`);
            if (!res.ok) throw new Error('err');
            return await res.json();
        } catch {
            return { online: false, players: { online: 0, max: 0, list: [] }, version: null, motd: { html: [], clean: [], raw: [] } };
        }
    },

    startPinging(ip, port, itemId) {
        this.stopPinging();

        const doPing = async () => {
            if (this.currentServerId != itemId) return false;
            const data = await this._ping(ip, port);
            this.updateStatus(data, itemId);
            return data.online;
        };

        doPing().then(online => {
            if (this.currentServerId != itemId) return;
            const delay = online
                ? 7000 + Math.random() * 10000
                : 50000 + Math.random() * 10000;
            this.pingInterval = setTimeout(() => this._loop(doPing), delay);
        });
    },

    _loop(doPing) {
        doPing().then(online => {
            if (this.currentServerId === null) return;
            const delay = online
                ? 7000 + Math.random() * 10000
                : 50000 + Math.random() * 10000;
            this.pingInterval = setTimeout(() => this._loop(doPing), delay);
        });
    },

    stopPinging() {
        if (this.pingInterval) {
            clearTimeout(this.pingInterval);
            this.pingInterval = null;
        }
    },

    // ── Обновление UI панели ──
    updateStatus(data, itemId) {
        // Строгая привязка
        if (this.currentServerId != itemId) return;

        const statusEl  = document.getElementById('serverStatus');
        const statsEl   = document.getElementById('serverStats');
        const playersEl = document.getElementById('serverPlayersList');
        const motdEl    = document.getElementById('panelMotd');
        const motdSec   = document.getElementById('panelMotdSection');
        const motdWrap  = document.getElementById('panelMotdWrapper');

        if (!statusEl) return;

        const indicator = statusEl.querySelector('.status-indicator');
        const text      = statusEl.querySelector('.status-text');

        if (typeof ServerPinger !== 'undefined') {
            ServerPinger.updateServerIndicator(
                indicator, 'status-indicator',
                data.online, data.players?.online || 0, data.players?.max || 0
            );
        } else {
            indicator.className = data.online
                ? 'status-indicator online pulsing'
                : 'status-indicator offline';
        }

        // Favicon
        if (data.favicon) {
            const faviconEl = document.getElementById('panelServerFavicon');
            const wrapperEl = document.getElementById('panelFaviconWrapper');
            if (faviconEl && wrapperEl) {
                faviconEl.src = data.favicon;
                wrapperEl.style.display = '';
            }
        }

        if (data.online) {
            text.textContent = `${data.players?.online || 0}/${data.players?.max || 0}`;

            if (statsEl) {
                statsEl.style.display = 'flex';
                const verEl = document.getElementById('serverVersion');
                if (verEl && data.version) verEl.textContent = data.version;
            }

            // MOTD
            const motdHtml = data.motd?.html || [];
            if (motdEl && motdSec && motdHtml.length) {
                motdEl.innerHTML = motdHtml.join('\n');
                motdSec.style.display = '';
                requestAnimationFrame(() => this._scaleMotd(motdWrap, motdEl));
            } else if (motdSec) {
                motdSec.style.display = 'none';
            }

            // Игроки
            this._renderPlayers(
                data.players?.list || [],
                playersEl,
                document.getElementById('playersListContainer')
            );
        } else {
            text.textContent = 'Офлайн';
            if (statsEl)   statsEl.style.display = 'none';
            if (playersEl) playersEl.style.display = 'none';
            if (motdSec)   motdSec.style.display = 'none';
        }

        this.updateTreeStatus(itemId, data.online, data.players);
    },

    // ── Рендер игроков (общий) ──
    _renderPlayers(raw, sectionEl, containerEl) {
        if (!sectionEl || !containerEl) return;

        let list = (raw || [])
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
            sectionEl.style.display = 'none';
            return;
        }

        sectionEl.style.display = 'block';
        containerEl.innerHTML = list.map(p => {
            const headSrc = p.head
                || (p.uuid ? `https://mc-heads.net/avatar/${p.uuid}/32` : null)
                || 'https://mc-heads.net/avatar/MHF_Steve/32';

            const skinAttr = p.skin ? `data-skin="${this._esc(p.skin)}"` : '';
            const clickAttr = p.skin ? `onclick="PanelServer.showSkinPreview(this)"` : '';

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
                <img src="${headSrc}" alt="${this._esc(p.name)}"
                     width="24" height="24" loading="lazy"
                     style="border-radius:3px; image-rendering:pixelated;"
                     onerror="this.src='https://mc-heads.net/avatar/MHF_Steve/32'">
                ${this._esc(p.name)}
                ${p.source && p.source !== 'mojang'
                    ? `<span style="font-size:9px;color:#666;margin-left:2px;">${this._esc(p.source)}</span>`
                    : ''}
            </span>`;
        }).join('');
    },

    // ── MOTD масштабирование ──
    _scaleMotd(wrapper, motd) {
        if (!wrapper || !motd) return;
        const cw = wrapper.clientWidth;
        if (cw < 520) {
            const s = cw / 520;
            motd.style.transform = `scale(${s})`;
            wrapper.style.height = Math.ceil(44 * s + 4) + 'px';
        } else {
            motd.style.transform = 'none';
            wrapper.style.height = 'auto';
        }
    },

    // ── Превью скина ──
    showSkinPreview(el) {
        const skinData = el.dataset?.skin;
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
    },

    // ── Дерево ──
    updateTreeStatus(itemId, online, players) {
        const dot = document.querySelector(`.folder-item[data-id="${itemId}"] .server-status-dot`);
        if (!dot) return;

        if (typeof ServerPinger !== 'undefined') {
            ServerPinger.updateServerIndicator(
                dot, 'server-status-dot',
                online, players?.online || 0, players?.max || 0
            );
        } else {
            dot.className = online ? 'server-status-dot online pulsing' : 'server-status-dot offline';
        }

        const countEl = dot.parentElement?.querySelector('.server-player-count');
        if (countEl) {
            countEl.textContent = online
                ? `${players?.online || 0}/${players?.max || 0}`
                : 'Офлайн';
            countEl.classList.toggle('offline-text', !online);
        }
    },

    // Репорт удалён — сервер сохраняет данные автоматически при пинге

    cleanup() {
        this.stopPinging();
        this.currentServerId = null;
    },

    resume() {
        if (!this.currentServerId || !window.currentPanelItem) return;
        const settings = typeof window.currentPanelItem.settings === 'string'
            ? JSON.parse(window.currentPanelItem.settings)
            : (window.currentPanelItem.settings || {});
        if (settings.ip) {
            this.startPinging(settings.ip, settings.port || 25565, this.currentServerId);
        }
    },

    setCurrentItem(item) {
        window.currentPanelItem = item;
    },

    _esc(str) {
        if (typeof esc === 'function') return esc(str);
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
};
</script>