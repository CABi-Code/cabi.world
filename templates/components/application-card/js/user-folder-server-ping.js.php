<script>
window.ServerTreePing = {
    servers: [],
    intervals: new Map(),
    bulkInterval: null,

    init() {
        this.collectServers();
        if (this.servers.length > 0) {
            this.bulkPingAll();
        }
    },

    collectServers() {
        const serverItems = document.querySelectorAll('.app-footer[data-type="server"]');
        this.servers = [];

        serverItems.forEach(item => {
            const id = item.dataset.id;
            const statusDot = item.querySelector('.server-status-dot');

            if (statusDot) {
                const ip = statusDot.dataset.ip;
                const port = statusDot.dataset.port || 25565;

                if (ip) {
                    this.servers.push({ id, ip, port: parseInt(port), element: item, online: false });
                }
            }
        });
    },

    // Массовый пинг
    async bulkPingAll() {
        if (this.servers.length === 0) return;

        try {
            const serverList = this.servers.map(s => ({ ip: s.ip, port: s.port }));
            const params = new URLSearchParams();
            params.set('servers', JSON.stringify(serverList));

            const res = await fetch(`/api/server-ping?${params}`);
            if (!res.ok) throw new Error('Bulk ping failed');
            const data = await res.json();

            const results = data.servers || [];
            results.forEach((result, idx) => {
                if (idx < this.servers.length) {
                    this.servers[idx].online = result.online;
                    this.updateTreeItem(this.servers[idx], result);
                }
            });
        } catch (e) {
            this.startPingingAll();
            return;
        }

        this.scheduleBulkNext();
    },

    scheduleBulkNext() {
        if (this.bulkInterval) clearTimeout(this.bulkInterval);
        const hasOnline = this.servers.some(s => s.online);
        const delay = hasOnline ? 15000 + Math.random() * 10000 : 60000 + Math.random() * 20000;
        this.bulkInterval = setTimeout(() => this.bulkPingAll(), delay);
    },

    startPingingAll() {
        this.servers.forEach(server => {
            ServerPinger.ping(server.ip, server.port).then(data => {
                server.online = data.online;
                this.updateTreeItem(server, data);
                this.scheduleNext(server, data.online);
            }).catch(() => {
                this.scheduleNext(server, false);
            });
        });
    },

    scheduleNext(server, isOnline) {
        if (this.intervals.has(server.id)) {
            clearTimeout(this.intervals.get(server.id));
        }
        const delay = isOnline ? 5000 + Math.random() * 10000 : 50000 + Math.random() * 10000;
        const timeout = setTimeout(() => {
            ServerPinger.ping(server.ip, server.port).then(data => {
                server.online = data.online;
                this.updateTreeItem(server, data);
                this.scheduleNext(server, data.online);
            }).catch(() => {
                this.updateTreeItem(server, { online: false });
                this.scheduleNext(server, false);
            });
        }, delay);
        this.intervals.set(server.id, timeout);
    },

    updateTreeItem(server, data) {
        const statusDot = server.element.querySelector('.server-status-dot');
        const countEl = server.element.querySelector('.server-player-count');
        if (!statusDot) return;

        ServerPinger.updateServerIndicator(
            statusDot, 'server-status-dot',
            data.online, data.players?.online || 0, data.players?.max || 0
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

    stopAll() {
        this.intervals.forEach(timeout => clearTimeout(timeout));
        this.intervals.clear();
        if (this.bulkInterval) {
            clearTimeout(this.bulkInterval);
            this.bulkInterval = null;
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        ServerTreePing.init();
        ServerPinger.initVisibilityControl();
    }, 1000);
});
</script>
