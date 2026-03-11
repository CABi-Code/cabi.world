<?php
/**
 * Контент элемента по умолчанию
 * @var array $item
 * @var array $owner
 * @var array $children
 */

use App\Repository\UserFolderRepository;
use App\Repository\GlobalServerRepository;

$hasServers = false;
$serverChildren = [];

// Предзагрузка favicon для серверов
if (!empty($children)) {
    $globalServerRepo = new GlobalServerRepository();
    foreach ($children as &$child) {
        if ($child['item_type'] === 'server') {
            $hasServers = true;
            $childSettings = !empty($child['settings'])
                ? (is_string($child['settings']) ? json_decode($child['settings'], true) : $child['settings'])
                : [];
            $child['_server_settings'] = $childSettings;
            $child['_server_favicon'] = null;
            if (!empty($childSettings['server_id'])) {
                $gs = $globalServerRepo->getById((int)$childSettings['server_id']);
                $child['_server_favicon'] = $gs['favicon'] ?? null;
            }
        }
    }
    unset($child);
}
?>
<div class="item-content">
    <?php if (!empty($children)): ?>
        <div class="item-children">
            <h2 class="section-title">Содержимое</h2>
            <div class="children-grid">
                <?php foreach ($children as $child): ?>
                    <?php
                    $childIcon = $child['icon'] ?? ($itemsMap[$child['item_type']]['icon'] ?? 'file');
                    $childColor = $child['color'] ?? ($itemsMap[$child['item_type']]['color'] ?? '#94a3b8');
                    $childSlug = $child['slug'] ?? null;
                    if ($childSlug) {
                        $childFullSlug = UserFolderRepository::getFullSlug($child['item_type'], $childSlug);
                        $childUrl = '/@' . $owner['login'] . '/' . $childFullSlug;
                    } else {
                        $childUrl = '/item/' . $child['id'];
                    }
                    $isServer = $child['item_type'] === 'server';
                    $childServerSettings = $child['_server_settings'] ?? [];
                    $childServerFavicon = $child['_server_favicon'] ?? null;
                    ?>
                    <a href="<?= e($childUrl) ?>" class="child-card<?= $isServer ? ' child-card-server' : '' ?>"
                       <?php if ($isServer && !empty($childServerSettings['ip'])): ?>
                       data-server-ip="<?= e($childServerSettings['ip']) ?>"
                       data-server-port="<?= e($childServerSettings['port'] ?? 25565) ?>"
                       <?php endif; ?>>
                        <?php if ($isServer && $childServerFavicon): ?>
                        <span class="child-icon child-server-favicon">
                            <img src="<?= e($childServerFavicon) ?>" width="20" height="20" alt="" style="border-radius:3px;image-rendering:pixelated;">
                        </span>
                        <?php else: ?>
                        <span class="child-icon<?= $isServer ? ' child-server-default-icon' : '' ?>" style="color: <?= e($childColor) ?>">
                            <svg width="20" height="20"><use href="#icon-<?= e($childIcon) ?>"/></svg>
                        </span>
                        <?php endif; ?>
                        <span class="child-name"><?= e($child['name']) ?></span>
                        <?php if ($isServer && !empty($childServerSettings['ip'])): ?>
                        <span class="child-server-status">
                            <span class="server-status-dot checking" title="Проверка..."></span>
                            <span class="server-player-count"></span>
                        </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="item-empty">
            <svg width="48" height="48"><use href="#icon-folder-open"/></svg>
            <p>Нет содержимого</p>
        </div>
    <?php endif; ?>
</div>

<?php if ($hasServers): ?>
<style>
.child-card-server {
    position: relative;
}
.child-server-status {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: #888;
    margin-top: 2px;
}
.child-server-status .server-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.child-server-status .server-status-dot.checking {
    background: #666;
    animation: pulse-check 1.5s infinite;
}
.child-server-status .server-status-dot.online {
    background: #22c55e;
}
.child-server-status .server-status-dot.online.pulsing {
    animation: pulse-online 1s ease-out;
}
.child-server-status .server-status-dot.offline {
    background: #ef4444;
}
.child-server-status .server-player-count.offline-text {
    color: #ef4444;
}
@keyframes pulse-check {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
}
@keyframes pulse-online {
    0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.6); }
    100% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
}
</style>

<script>
(function() {
    const serverCards = document.querySelectorAll('.child-card-server[data-server-ip]');
    if (!serverCards.length) return;

    const servers = [];
    serverCards.forEach(card => {
        servers.push({
            ip: card.dataset.serverIp,
            port: parseInt(card.dataset.serverPort) || 25565,
            element: card,
            online: false
        });
    });

    async function pingAll() {
        const serverList = servers.map(s => ({ ip: s.ip, port: s.port }));
        try {
            const params = new URLSearchParams();
            params.set('servers', JSON.stringify(serverList));
            const res = await fetch(`/api/server-ping?${params}`);
            if (!res.ok) throw new Error('Bulk ping failed');
            const data = await res.json();
            const results = data.servers || [];
            results.forEach((result, idx) => {
                if (idx < servers.length) {
                    servers[idx].online = result.online;
                    updateCard(servers[idx], result);
                }
            });
        } catch (e) {
            // Фоллбэк на поштучный пинг
            for (const server of servers) {
                try {
                    const res = await fetch(`/api/server-ping?ip=${encodeURIComponent(server.ip)}&port=${server.port}`);
                    if (!res.ok) continue;
                    const result = await res.json();
                    server.online = result.online;
                    updateCard(server, result);
                } catch {}
            }
        }
        scheduleNext();
    }

    function updateCard(server, data) {
        const dot = server.element.querySelector('.server-status-dot');
        const countEl = server.element.querySelector('.server-player-count');
        if (!dot) return;

        if (data.online) {
            dot.className = 'server-status-dot online pulsing';
            dot.classList.add('pulse');
            setTimeout(() => dot.classList.remove('pulse'), 1000);
            if (countEl) {
                countEl.textContent = `${data.players?.online || 0}/${data.players?.max || 0}`;
                countEl.classList.remove('offline-text');
            }
        } else {
            dot.className = 'server-status-dot offline';
            if (countEl) {
                countEl.textContent = 'Офлайн';
                countEl.classList.add('offline-text');
            }
        }

        // Обновляем favicon
        if (data.favicon) {
            const iconEl = server.element.querySelector('.child-icon.child-server-default-icon, .child-icon.child-server-favicon');
            if (iconEl) {
                const existingImg = iconEl.querySelector('img');
                if (existingImg) {
                    existingImg.src = data.favicon;
                } else {
                    iconEl.innerHTML = `<img src="${data.favicon}" width="20" height="20" alt="" style="border-radius:3px;image-rendering:pixelated;">`;
                    iconEl.classList.remove('child-server-default-icon');
                    iconEl.classList.add('child-server-favicon');
                }
            }
        }
    }

    let pingTimeout = null;
    function scheduleNext() {
        if (pingTimeout) clearTimeout(pingTimeout);
        const hasOnline = servers.some(s => s.online);
        const delay = hasOnline
            ? 15000 + Math.random() * 10000
            : 60000 + Math.random() * 20000;
        pingTimeout = setTimeout(() => pingAll(), delay);
    }

    // Visibility control
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            if (pingTimeout) { clearTimeout(pingTimeout); pingTimeout = null; }
        } else if (document.visibilityState === 'visible') {
            pingAll();
        }
    });

    // Init
    setTimeout(() => pingAll(), 500);
})();
</script>
<?php endif; ?>
