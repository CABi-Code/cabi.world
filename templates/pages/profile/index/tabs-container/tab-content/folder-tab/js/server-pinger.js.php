<script>
window.ServerPinger = {
    /**
     * Пинг через наш бэкенд (он сам делает fallback на mcsrvstat)
     */
    async ping(ip, port) {
        try {
            const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(ip)}&port=${port}`);
            if (!res.ok) throw new Error('Backend error');
            const data = await res.json();

            return {
                online:  data.online === true,
                source:  data.source || 'unknown',
                players: {
                    online: data.players?.online || 0,
                    max:    data.players?.max || 0,
                    list:   data.players?.list || data.players?.sample || []
                },
                version: data.version || null,
                motd: {
                    raw:   data.motd?.raw   || [],
                    clean: data.motd?.clean || [],
                    html:  data.motd?.html  || []
                },
                favicon:  data.favicon || null,
                latency:  data.latency || null,
                hostname: data.hostname || null
            };
        } catch (e) {
            console.warn('Backend ping failed, trying mcsrvstat directly:', e);
            return this.pingMcsrvstat(ip, port);
        }
    },

    /**
     * Прямой фоллбэк на mcsrvstat (если бэкенд недоступен)
     */
    async pingMcsrvstat(ip, port) {
        try {
            const address = port !== 25565 ? `${ip}:${port}` : ip;
            const res = await fetch(`https://api.mcsrvstat.us/3/${encodeURIComponent(address)}`);
            if (!res.ok) throw new Error('mcsrvstat error');
            const raw = await res.json();

            if (!raw.online) {
                return this.offlineResult();
            }

            return {
                online:  true,
                source:  'mcsrvstat-direct',
                players: {
                    online: raw.players?.online || 0,
                    max:    raw.players?.max || 0,
                    list:   (raw.players?.list || []).map(p =>
                        typeof p === 'string' ? { name: p } : p
                    )
                },
                version:  raw.version || null,
                motd: {
                    raw:   raw.motd?.raw   || [],
                    clean: raw.motd?.clean || [],
                    html:  raw.motd?.html  || []
                },
                favicon:  raw.icon || null,
                latency:  null,
                hostname: raw.hostname || null
            };
        } catch (e) {
            console.warn('mcsrvstat direct ping failed:', e);
            return this.offlineResult();
        }
    },

    offlineResult() {
        return {
            online: false, source: 'none',
            players: { online: 0, max: 0, list: [] },
            version: null,
            motd: { raw: [], clean: [], html: [] },
            favicon: null, latency: null, hostname: null
        };
    },

    /**
     * Планировщик следующего пинга
     */
    scheduleNext(context, isOnline, pingFn) {
        const delay = isOnline
            ? 7000  + Math.random() * 10000   // 7-17с если онлайн
            : 50000 + Math.random() * 10000;  // 50-60с если офлайн

        return setTimeout(async () => {
            try {
                const online = await pingFn();
                context.pingInterval = this.scheduleNext(context, online, pingFn);
            } catch {
                context.pingInterval = this.scheduleNext(context, false, pingFn);
            }
        }, delay);
    },

    /**
     * Обновление CSS-индикатора статуса
     */
    updateServerIndicator(el, baseClass, online, playersOnline = 0, playersMax = 0) {
        if (!el || !baseClass) return;

        if (online) {
            el.className = `${baseClass} online pulsing`;
            el.classList.add('pulse');
            setTimeout(() => el.classList.remove('pulse'), 1000);
        } else {
            el.className = `${baseClass} offline`;
        }
    },

    /* ── Visibility control ── */
    isVisible: true,
    visibilityHandler: null,

    initVisibilityControl() {
        if (this.visibilityHandler) return;

        this.visibilityHandler = () => {
            const wasVisible = this.isVisible;
            this.isVisible = document.visibilityState === 'visible';

            if (wasVisible && !this.isVisible) {
                ServerTreePing.stopAll();
                PanelServer.stopPinging();
            } else if (!wasVisible && this.isVisible) {
                setTimeout(() => {
                    if (ServerTreePing.servers?.length) {
                        ServerTreePing.startPingingAll();
                    }
                    PanelServer.resume();
                }, 500);
            }
        };

        document.addEventListener('visibilitychange', this.visibilityHandler);
        window.addEventListener('beforeunload', () => {
            ServerTreePing.stopAll();
            PanelServer.stopPinging();
        });
    }
};
</script>