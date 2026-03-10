<script>
window.ServerTreePing = {
    servers: [],
    intervals: new Map(),

    init() {
        this.collectServers();
        this.startPingingAll();
    },

    collectServers() {
        this.servers = [];
        document.querySelectorAll('.folder-item[data-type="server"]').forEach(item => {
            const dot = item.querySelector('.server-status-dot');
            if (dot?.dataset.ip) {
                this.servers.push({
                    id:      item.dataset.id,
                    ip:      dot.dataset.ip,
                    port:    parseInt(dot.dataset.port) || 25565,
                    element: item
                });
            }
        });
    },

    startPingingAll() {
        this.servers.forEach(server => {
            ServerPinger.ping(server.ip, server.port).then(data => {
                this.updateTreeItem(server, data);
                this.reportToServer(server.id, data);
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

        const delay = isOnline
            ? 5000  + Math.random() * 10000
            : 50000 + Math.random() * 10000;

        const timeout = setTimeout(() => {
            ServerPinger.ping(server.ip, server.port).then(data => {
                this.updateTreeItem(server, data);
                this.reportToServer(server.id, data);
                this.scheduleNext(server, data.online);
            }).catch(() => {
                this.updateTreeItem(server, ServerPinger.offlineResult());
                this.scheduleNext(server, false);
            });
        }, delay);

        this.intervals.set(server.id, timeout);
    },

    updateTreeItem(server, data) {
        const statusDot = server.element.querySelector('.server-status-dot');
        const countEl   = server.element.querySelector('.server-player-count');
        if (!statusDot) return;

        ServerPinger.updateServerIndicator(
            statusDot, 'server-status-dot',
            data.online, data.players?.online || 0, data.players?.max || 0
        );

        if (countEl) {
            if (data.online) {
                countEl.textContent = `${data.players.online}/${data.players.max}`;
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
                    item_id:        itemId,
                    online:         data.online,
                    players_online: data.players?.online || 0,
                    players_max:    data.players?.max || 0,
                    players_sample: data.players?.list || [],
                    version:        data.version || null
                })
            });
        } catch (e) { /* ignore */ }
    },

    stopAll() {
        this.intervals.forEach(t => clearTimeout(t));
        this.intervals.clear();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        ServerTreePing.init();
        ServerPinger.initVisibilityControl();
    }, 1000);
});
</script>