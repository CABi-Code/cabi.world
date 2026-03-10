<script>
window.ServerPinger = {
    scheduleNext(serverOrPanel, isOnline, pingFn) {
        const delay = isOnline 
            ? 7000 + Math.random() * 10000 
            : 50000 + Math.random() * 10000;

        return setTimeout(() => {
            pingFn().then(online => {
                this.scheduleNext(serverOrPanel, online, pingFn);
            });
        }, delay);
    },

    // Общий метод пинга
    async ping(ip, port) {
        try {
            const url = `https://api.mcsrvstat.us/2/${encodeURIComponent(ip)}:${port}`;
            const res = await fetch(url);

            if (!res.ok) throw new Error('Network error');

            const raw = await res.json();
			
			console.log('Понг!');
            return {
                online: raw.online === true,
                players: {
                    online: raw.players?.online || 0,
                    max:    raw.players?.max || 0,
                    sample: raw.players?.list || []
                },
                version: raw.version?.name || raw.version || null,
                description: {
                    text: raw.motd?.clean || raw.motd?.raw || raw.motd || ''
                }
            };
        } catch (e) {
            console.warn('Client ping failed:', e);
            return {
                online: false,
                players: { online: 0, max: 0, sample: [] },
                version: null,
                description: { text: '' }
            };
        }
    },
	
    /**
     * Общая функция обновления индикатора статуса
     * @param {HTMLElement} indicatorEl - элемент .status-indicator или .server-status-dot
     * @param {boolean} online
     * @param {number} playersOnline
     * @param {number} playersMax
     */
	updateServerIndicator(indicatorEl, baseClass, online, playersOnline = 0, playersMax = 0) {
		if (!indicatorEl || !baseClass) return;

		if (online) {
			indicatorEl.className = `${baseClass} online pulsing`;
			indicatorEl.classList.add('pulse');

			setTimeout(() => {
				indicatorEl.classList.remove('pulse');
			}, 1000);
		} else {
			indicatorEl.className = `${baseClass} offline`;
		}
	},
	
    isVisible: true,
    visibilityHandler: null,

    initVisibilityControl() {
        if (this.visibilityHandler) return; // уже инициализировано

        this.visibilityHandler = () => {
            const wasVisible = this.isVisible;
            this.isVisible = document.visibilityState === 'visible';

            if (wasVisible && !this.isVisible) {
                // Страница стала невидимой → полностью останавливаем все пинги
                console.log('📴 Страница скрыта → останавливаем все пинги');
                ServerTreePing.stopAll();
            } 
            else if (!wasVisible && this.isVisible) {
                // Страница снова видимая → возобновляем пинги
                console.log('📶 Страница видима → возобновляем пинги');
                setTimeout(() => {
                    if (ServerTreePing.servers && ServerTreePing.servers.length > 0) {
                        ServerTreePing.startPingingAll();
                    }
                    // Панель возобновится автоматически при afterRender, если она открыта
                }, 500);
            }
        };

        document.addEventListener('visibilitychange', this.visibilityHandler);

        // Дополнительно: при закрытии вкладки
        window.addEventListener('beforeunload', () => {
            ServerTreePing.stopAll();
        });
    }
};
</script>