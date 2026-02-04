<script>
/**
 * Панель для элемента типа "server"
 */
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
        
        // Информация о сервере
        if (settings.ip) {
            const port = settings.port && settings.port !== 25565 ? ':' + settings.port : '';
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
            
            // Query настройки если есть
            if (settings.query_ip) {
                html += `<div class="panel-server-query">
                    <span class="query-label">Query:</span>
                    <span class="query-value">${esc(settings.query_ip)}:${settings.query_port || 25565}</span>
                </div>`;
            }
        }
        
        // Статистика игроков (если есть данные)
        html += `<div class="panel-server-stats" id="serverStats" style="display:none;">
			<div class="server-status" id="serverStatus">
				<span class="status-indicator checking"></span>
				<span class="status-text">Проверка...</span>
			</div>
            <div class="server-version" id="serverVersion"></div>
        </div>`;
        
        // Список игроков
        html += `<div class="panel-server-players" id="serverPlayersList" style="display:none;">
			<div class="server-players" style="display:flex;align-items:center;gap:8px;">
				<svg width="16" height="16"><use href="#icon-users"/></svg>
				<div class="panel-children-title">Игроки онлайн:</div>
			</div>
            <div class="players-list" id="playersListContainer"></div>
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
        const fullAddress = port && port !== '25565' ? `${ip}:${port}` : ip;
        
        navigator.clipboard.writeText(fullAddress).then(() => {
            const btn = document.getElementById('copyIpBtn');
            if (!btn) return;
            
            btn.classList.add('copied');
            btn.innerHTML = `<svg width="14" height="14"><use href="#icon-check"/></svg>
                <span>Скопировано!</span>`;
            
            setTimeout(() => {
                btn.classList.remove('copied');
                btn.innerHTML = `<svg width="14" height="14"><use href="#icon-copy"/></svg>
                    <span>Копировать</span>`;
            }, 2000);
        });
    },
    
	startPinging(ip, port, itemId) {
		this.stopPinging();

		const doPing = async () => {
			const data = await ServerPinger.ping(ip, port);
			this.updateStatus(data, itemId);
			this.sendStatusToServer(itemId, data);
			return data.online;
		};

		doPing().then(online => {
			this.pingInterval = ServerPinger.scheduleNext(this, online, doPing);
		});
	},
    
    stopPinging() {
        if (this.pingInterval) {
            clearTimeout(this.pingInterval);
            this.pingInterval = null;
        }
    },
    
	async pingServer(ip, port, itemId) {
		const data = await ServerPinger.ping(ip, port);   // ← используем общий метод

		this.updateStatus(data, itemId);
		this.sendStatusToServer(itemId, data);

		return data.online;
	},
	
	updateStatus(data, itemId) {
		const statusEl = document.getElementById('serverStatus');
		const statsEl = document.getElementById('serverStats');
		const playersEl = document.getElementById('serverPlayersList');
		
		if (!statusEl) return;
		
		const indicator = statusEl.querySelector('.status-indicator');
		const text = statusEl.querySelector('.status-text');

		// Используем общую функцию
		ServerPinger.updateServerIndicator(
			indicator, 
			'status-indicator', 
			data.online,
			data.players?.online || 0,
			data.players?.max || 0
		);

		if (data.online) {
			text.textContent = `${data.players?.online || 0}/${data.players?.max || 0}`;

			if (statsEl) {
				statsEl.style.display = 'flex';
				if (data.version) document.getElementById('serverVersion').textContent = data.version;
			}

			// Список игроков (ваш текущий хороший код)
			const playersData = data.players || {};
			let sample = playersData.sample || playersData.list || [];
			if (!Array.isArray(sample)) sample = [];

			const playerNames = sample
				.map(item => typeof item === 'string' ? item.trim() : (item?.name || '').trim())
				.filter(name => name.length > 0);

			if (playersEl && playerNames.length > 0) {
				playersEl.style.display = 'block';
				const container = document.getElementById('playersListContainer');
				container.innerHTML = playerNames.map(name => `<span class="player-name">${esc(name)}</span>`).join('');
			} else if (playersEl) {
				playersEl.style.display = 'none';
			}
		} else {
			text.textContent = 'Офлайн';
			if (statsEl) statsEl.style.display = 'none';
			if (playersEl) playersEl.style.display = 'none';
		}

		// Обновляем дерево (лучше оставить, но можно улучшить позже)
		this.updateTreeStatus(itemId, data.online, data.players);
	},
    
    updateTreeStatus(itemId, online, players) {
        const treeItem = document.querySelector(`.folder-item[data-id="${itemId}"] .server-status-dot`);
        if (!treeItem) return;
        
        if (online) {
            treeItem.className = 'server-status-dot online pulsing';
            treeItem.title = `${players?.online || 0}/${players?.max || 0} игроков`;
            
            const countEl = treeItem.parentElement.querySelector('.server-player-count');
            if (countEl) {
                countEl.textContent = `${players?.online || 0}/${players?.max || 0}`;
            }
        } else {
            treeItem.className = 'server-status-dot offline';
            treeItem.title = 'Офлайн';
            
            const countEl = treeItem.parentElement.querySelector('.server-player-count');
            if (countEl) {
                countEl.textContent = 'Офлайн';
            }
        }
    },
    
    async sendStatusToServer(itemId, data) {
        try {
            await fetch('/api/server-ping/report', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrf 
                },
                body: JSON.stringify({
                    item_id: itemId,
                    online: data.online,
                    players_online: data.players?.online || 0,
                    players_max: data.players?.max || 0,
                    players_sample: data.players?.sample || [],
                    version: data.version || null,
                    motd: data.description?.text || null
                })
            });
        } catch (e) {
            console.warn('Failed to report server status:', e);
        }
    },
    
    cleanup() {
        this.stopPinging();
        this.currentServerId = null;
    },
	
	// Возобновить пинг (вызывается, когда страница снова становится видимой)
	resume() {
		if (!this.currentServerId) return;

		const settings = typeof window.currentPanelItem?.settings === 'string'
			? JSON.parse(window.currentPanelItem.settings)
			: (window.currentPanelItem?.settings || {});

		if (settings.ip) {
			this.startPinging(settings.ip, settings.port || 25565, this.currentServerId);
		}
	},

	// Сохраняем текущий item для resume
	setCurrentItem(item) {
		window.currentPanelItem = item;
	}
};
</script>
