<?php
/**
 * Панель управления - Заявки
 * 
 * @var array $user
 * @var array $applications
 * @var int $pendingCount
 * @var int $totalPages
 * @var int $page
 * @var string $status
 */

use App\Core\Role;

?>

<div class="admin-page">
    <div class="admin-header">
        <h1 class="admin-title">
            <svg width="24" height="24"><use href="#icon-settings"/></svg>
            Панель управления
        </h1>
        <div class="admin-user">
            <?= Role::badge($user['role']) ?>
            <span><?= e($user['username']) ?></span>
        </div>
    </div>
    
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="/admin" class="admin-nav-item active">
                    <svg width="18" height="18"><use href="#icon-send"/></svg>
                    Заявки
                    <?php if ($pendingCount > 0): ?>
                        <span class="admin-nav-badge"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <!-- Тут можно добавить другие пункты меню -->
            </nav>
        </aside>
        
        <main class="admin-content">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2>Модерация заявок</h2>
                    <div class="admin-filters">
                        <a href="/admin?status=pending" class="admin-filter-btn <?= $status === 'pending' ? 'active' : '' ?>">
                            На рассмотрении
                            <?php if ($pendingCount > 0): ?>
                                <span class="filter-count"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/admin?status=accepted" class="admin-filter-btn <?= $status === 'accepted' ? 'active' : '' ?>">Одобренные</a>
                        <a href="/admin?status=rejected" class="admin-filter-btn <?= $status === 'rejected' ? 'active' : '' ?>">Отклонённые</a>
                        <a href="/admin" class="admin-filter-btn <?= !$status ? 'active' : '' ?>">Все</a>
                    </div>
                </div>
                
                <?php if (empty($applications)): ?>
                    <div class="admin-empty">
                        <svg width="48" height="48"><use href="#icon-check"/></svg>
                        <p>Нет заявок для отображения</p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Пользователь</th>
                                    <th>Модпак</th>
                                    <th>Сообщение</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr data-app-id="<?= $app['id'] ?>">
                                        <td class="admin-td-id">#<?= $app['id'] ?></td>
                                        <td>
                                            <a href="/@<?= e($app['login']) ?>" class="admin-user-link">
                                                <div class="admin-avatar">
                                                    <?php if ($app['avatar']): ?>
                                                        <img src="<?= e($app['avatar']) ?>" alt="">
                                                    <?php else: ?>
                                                        <?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="admin-username">
                                                        <?= e($app['username']) ?>
                                                        <?= Role::badge($app['role'] ?? 'user') ?>
                                                    </div>
                                                    <div class="admin-login">@<?= e($app['login']) ?></div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="admin-modpack-link">
                                                <?php if ($app['icon_url']): ?>
                                                    <img src="<?= e($app['icon_url']) ?>" alt="" class="admin-mp-icon">
                                                <?php endif; ?>
                                                <?= e($app['modpack_name']) ?>
                                            </a>
                                        </td>
                                        <td class="admin-td-message">
                                            <div class="admin-message-preview" title="<?= e($app['message']) ?>">
                                                <?= e(mb_substr($app['message'], 0, 100)) ?><?= mb_strlen($app['message']) > 100 ? '...' : '' ?>
                                            </div>
                                            <?php if ($app['relevant_until']): ?>
                                                <div class="admin-relevant">
                                                    До: <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="admin-td-date">
                                            <?= date('d.m.Y', strtotime($app['created_at'])) ?><br>
                                            <span class="admin-time"><?= date('H:i', strtotime($app['created_at'])) ?></span>
                                        </td>
                                        <td>
                                            <span class="app-status status-<?= $app['status'] ?>" data-status>
                                                <?= match($app['status']) { 
                                                    'pending' => 'Ожидает', 
                                                    'accepted' => 'Одобрена', 
                                                    'rejected' => 'Отклонена', 
                                                    default => $app['status'] 
                                                } ?>
                                            </span>
                                        </td>
                                        <td class="admin-td-actions">
                                            <div class="admin-actions">
                                                <?php if ($app['status'] !== 'accepted'): ?>
                                                    <button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(<?= $app['id'] ?>, 'accepted')" title="Одобрить">
                                                        <svg width="14" height="14"><use href="#icon-check"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($app['status'] !== 'rejected'): ?>
                                                    <button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(<?= $app['id'] ?>, 'rejected')" title="Отклонить">
                                                        <svg width="14" height="14"><use href="#icon-x"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-ghost btn-sm" onclick="viewAppDetails(<?= $app['id'] ?>)" title="Подробнее">
                                                    <svg width="14" height="14"><use href="#icon-eye"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="margin-top:1rem;">
                            <?php 
                            $baseUrl = '/admin' . ($status ? '?status=' . e($status) : '');
                            $sep = $status ? '&' : '?';
                            ?>
                            <?php if ($page > 1): ?>
                                <a href="<?= $baseUrl . $sep ?>page=<?= $page - 1 ?>" class="page-item">&laquo;</a>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="<?= $baseUrl . $sep ?>page=<?= $i ?>" class="page-item <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="<?= $baseUrl . $sep ?>page=<?= $page + 1 ?>" class="page-item">&raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Модальное окно просмотра заявки -->
<div id="appDetailsModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content modal-lg">
        <h3>Детали заявки</h3>
        <div id="appDetailsContent">
            <!-- Контент загружается через JS -->
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-close>Закрыть</button>
            <button type="button" class="btn admin-btn-accept btn-sm" id="modalAcceptBtn">
                <svg width="14" height="14"><use href="#icon-check"/></svg> Одобрить
            </button>
            <button type="button" class="btn admin-btn-reject btn-sm" id="modalRejectBtn">
                <svg width="14" height="14"><use href="#icon-x"/></svg> Отклонить
            </button>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
let currentAppId = null;

async function setAppStatus(id, status) {
    const action = status === 'accepted' ? 'одобрить' : 'отклонить';
    if (!confirm(`Вы уверены, что хотите ${action} заявку #${id}?`)) return;
    
    try {
        const res = await fetch('/api/admin/application/status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ id, status })
        });
        const data = await res.json();
        
        if (data.success) {
            // Обновляем строку в таблице
            const row = document.querySelector(`tr[data-app-id="${id}"]`);
            if (row) {
                const statusEl = row.querySelector('[data-status]');
                statusEl.className = `app-status status-${status}`;
                statusEl.textContent = status === 'accepted' ? 'Одобрена' : 'Отклонена';
                
                // Обновляем кнопки
                const actionsEl = row.querySelector('.admin-actions');
                actionsEl.innerHTML = `
                    ${status !== 'accepted' ? `<button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(${id}, 'accepted')" title="Одобрить"><svg width="14" height="14"><use href="#icon-check"/></svg></button>` : ''}
                    ${status !== 'rejected' ? `<button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(${id}, 'rejected')" title="Отклонить"><svg width="14" height="14"><use href="#icon-x"/></svg></button>` : ''}
                    <button class="btn btn-ghost btn-sm" onclick="viewAppDetails(${id})" title="Подробнее"><svg width="14" height="14"><use href="#icon-eye"/></svg></button>
                `;
            }
        } else {
            alert(data.error || 'Ошибка');
        }
    } catch (err) {
        alert('Ошибка сети');
    }
}

async function viewAppDetails(id) {
    currentAppId = id;
    const modal = document.getElementById('appDetailsModal');
    const content = document.getElementById('appDetailsContent');
    
    content.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
    modal.style.display = 'flex';
    
    try {
        const res = await fetch(`/api/admin/application/${id}`, {
            headers: { 'X-CSRF-Token': csrf }
        });
        const data = await res.json();
        
        if (data.success && data.application) {
            const app = data.application;
            content.innerHTML = `
                <div class="app-detail">
                    <div class="app-detail-header">
                        <a href="/@${app.login}" class="feed-user">
                            <div class="feed-avatar" ${app.avatar ? '' : `style="background:linear-gradient(135deg,${(app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',')[0]},${(app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',')[1] || '#8b5cf6'})"`}>
                                ${app.avatar ? `<img src="${app.avatar}" alt="">` : app.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="feed-name">${app.username}</div>
                                <div class="feed-login">@${app.login}</div>
                            </div>
                        </a>
                        <a href="/modpack/${app.platform}/${app.slug}" class="feed-modpack">
                            ${app.icon_url ? `<img src="${app.icon_url}" alt="" class="feed-mp-icon">` : ''}
                            ${app.modpack_name}
                        </a>
                    </div>
                    <div class="app-detail-body">
                        <p class="app-detail-message">${app.message.replace(/\n/g, '<br>')}</p>
                        ${app.relevant_until ? `<p style="font-size:0.8125rem;color:var(--text-muted);margin-top:0.5rem;">Актуально до: ${new Date(app.relevant_until).toLocaleDateString('ru')}</p>` : ''}
                    </div>
                    <div class="app-detail-contacts">
                        ${app.contact_discord ? `<span class="contact-btn discord"><svg width="14" height="14"><use href="#icon-discord"/></svg>${app.contact_discord}</span>` : ''}
                        ${app.contact_telegram ? `<a href="https://t.me/${app.contact_telegram.replace('@', '')}" class="contact-btn telegram" target="_blank"><svg width="14" height="14"><use href="#icon-telegram"/></svg>${app.contact_telegram}</a>` : ''}
                        ${app.contact_vk ? `<a href="https://vk.com/${app.contact_vk}" class="contact-btn vk" target="_blank"><svg width="14" height="14"><use href="#icon-vk"/></svg>${app.contact_vk}</a>` : ''}
                    </div>
                    <div class="app-detail-meta">
                        <span>Создано: ${new Date(app.created_at).toLocaleString('ru')}</span>
                        <span class="app-status status-${app.status}">${app.status === 'pending' ? 'Ожидает' : app.status === 'accepted' ? 'Одобрена' : 'Отклонена'}</span>
                    </div>
                </div>
            `;
            
            // Обновляем кнопки модалки
            document.getElementById('modalAcceptBtn').style.display = app.status !== 'accepted' ? '' : 'none';
            document.getElementById('modalRejectBtn').style.display = app.status !== 'rejected' ? '' : 'none';
        } else {
            content.innerHTML = '<div class="alert alert-error">Заявка не найдена</div>';
        }
    } catch (err) {
        content.innerHTML = '<div class="alert alert-error">Ошибка загрузки</div>';
    }
}

document.getElementById('modalAcceptBtn')?.addEventListener('click', () => {
    if (currentAppId) {
        setAppStatus(currentAppId, 'accepted');
        document.getElementById('appDetailsModal').style.display = 'none';
    }
});

document.getElementById('modalRejectBtn')?.addEventListener('click', () => {
    if (currentAppId) {
        setAppStatus(currentAppId, 'rejected');
        document.getElementById('appDetailsModal').style.display = 'none';
    }
});

// Закрытие модалки
document.querySelectorAll('#appDetailsModal [data-close]').forEach(el => {
    el.addEventListener('click', () => {
        document.getElementById('appDetailsModal').style.display = 'none';
    });
});
</script>
