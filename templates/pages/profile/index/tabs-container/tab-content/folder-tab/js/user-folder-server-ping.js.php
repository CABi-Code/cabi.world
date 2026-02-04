<script>
/**
 * Пинг серверов в дереве папок
 * Автоматически пингует все серверы и обновляет статусы
 */
window.ServerTreePing = {
    servers: [],
    intervals: new Map(),
    
    init() {
        this.collectServers();
        this.startPingingAll();
    },
    
    collectServers() {
        const serverItems = document.querySelectorAll('.folder-item[data-type="server"]');
        this.servers = [];
        
        serverItems.forEach(item => {
            const id = item.dataset.id;
            const statusDot = item.querySelector('.server-status-dot');
            
            if (statusDot) {
                const ip = statusDot.dataset.ip;
                const port = statusDot.dataset.port || 25565;
                
                if (ip) {
                    this.servers.push({ id, ip, port: parseInt(port), element: item });
                }
            }
        });
    },
		
	startPingingAll() {
		this.servers.forEach(server => {
			// Первый пинг сразу (без дублирования)
			ServerPinger.ping(server.ip, server.port).then(data => {
				this.updateTreeItem(server, data);
				this.reportToServer(server.id, data);
				
				// Планируем следующий пинг по реальному статусу
				this.scheduleNext(server, data.online);
			}).catch(() => {
				// Если первый запрос упал
				this.scheduleNext(server, false);
			});
		});
	},
	
	scheduleNext(server, isOnline) {
		if (this.intervals.has(server.id)) {
			clearTimeout(this.intervals.get(server.id));
		}

		const delay = isOnline 
			? 5000 + Math.random() * 10000 
			: 50000 + Math.random() * 10000;

		const timeout = setTimeout(() => {
			ServerPinger.ping(server.ip, server.port).then(data => {
				this.updateTreeItem(server, data);
				this.reportToServer(server.id, data);
				this.scheduleNext(server, data.online);
			}).catch(() => {
				this.updateTreeItem(server, { online: false });
				this.scheduleNext(server, false);
			});
		}, delay);

		this.intervals.set(server.id, timeout);
	},
	
	async pingServer(server) {
		const data = await ServerPinger.ping(server.ip, server.port);   // ← общий метод

		this.updateTreeItem(server, data);
		this.reportToServer(server.id, data);

		return data;
	},
    
	updateTreeItem(server, data) {
		const statusDot = server.element.querySelector('.server-status-dot');
		const countEl = server.element.querySelector('.server-player-count');

		if (!statusDot) return;

		// Общая функция (одни и те же классы!)
		ServerPinger.updateServerIndicator(
			statusDot,
			'server-status-dot',
			data.online,
			data.players?.online || 0,
			data.players?.max || 0
		);

		if (countEl) {
			if (data.online) {
				countEl.textContent = `${data.players?.online || 0}/${data.players?.max || 0}`;
				countEl.classList.remove('offline-text');
			} else {
				countEl.textContent = 'Офлайн';
				countEl.classList.add('offline-text');
			}
		}
	},
    
    async reportToServer(itemId, data) {
        if (!window.csrf) return;
        
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
                    version: data.version || null
                })
            });
        } catch (e) {
            // Игнорируем ошибки отправки
        }
    },
    
    stopAll() {
        this.intervals.forEach(timeout => clearTimeout(timeout));
        this.intervals.clear();
    }
};

// Автозапуск после загрузки iconMap
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        ServerTreePing.init();
        ServerPinger.initVisibilityControl();   // ← остановка пинга
    }, 1000);
});
</script>
