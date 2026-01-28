<?php
/**
 * Панель управления - Заявки
 * 
 * @var array $user
 * @var array $applications
 * @var int $pendingCount
 * @var int $totalPages
 * @var int $page
 * @var string|null $status
 */

use App\Core\Role;

// Функция для отображения аватара с градиентом
function renderAdminAvatar(array $userData): string {
    $colors = explode(',', $userData['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
    $style = 'background:linear-gradient(135deg,' . ($colors[0] ?? '#3b82f6') . ',' . ($colors[1] ?? $colors[0] ?? '#8b5cf6') . ')';
    
    if (!empty($userData['avatar'])) {
        return '<img src="' . e($userData['avatar']) . '" alt="">';
    }
    return '<span style="' . $style . '">' . mb_strtoupper(mb_substr($userData['username'] ?? 'U', 0, 1)) . '</span>';
}
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
                                    <?php
                                    $avatarColors = explode(',', $app['avatar_bg_value'] ?? '#3b82f6,#8b5cf6');
                                    $avatarStyle = 'background:linear-gradient(135deg,' . ($avatarColors[0] ?? '#3b82f6') . ',' . ($avatarColors[1] ?? $avatarColors[0]) . ')';
                                    ?>
                                    <tr data-app-id="<?= $app['id'] ?>">
                                        <td class="admin-td-id">#<?= $app['id'] ?></td>
                                        <td>
                                            <a href="/@<?= e($app['login']) ?>" class="admin-user-link">
                                                <div class="admin-avatar" style="<?= empty($app['avatar']) ? $avatarStyle : '' ?>">
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
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Детали заявки</h3>
                <button type="button" class="modal-close" data-modal-close>
                    <svg width="20" height="20"><use href="#icon-x"/></svg>
                </button>
            </div>
            <div class="modal-body" id="appDetailsContent">
                <!-- Контент загружается через JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close>Закрыть</button>
                <button type="button" class="btn admin-btn-accept btn-sm" id="modalAcceptBtn">
                    <svg width="14" height="14"><use href="#icon-check"/></svg> Одобрить
                </button>
                <button type="button" class="btn admin-btn-reject btn-sm" id="modalRejectBtn">
                    <svg width="14" height="14"><use href="#icon-x"/></svg> Отклонить
                </button>
            </div>
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
            const row = document.querySelector(`tr[data-app-id="${id}"]`);
            if (row) {
                const statusEl = row.querySelector('[data-status]');
                statusEl.className = `app-status status-${status}`;
                statusEl.textContent = status === 'accepted' ? 'Одобрена' : 'Отклонена';
                
                const actionsEl = row.querySelector('.admin-actions');
                actionsEl.innerHTML = `
                    ${status !== 'accepted' ? `<button class="btn btn-sm admin-btn-accept" onclick="setAppStatus(${id}, 'accepted')" title="Одобрить"><svg width="14" height="14"><use href="#icon-check"/></svg></button>` : ''}
                    ${status !== 'rejected' ? `<button class="btn btn-sm admin-btn-reject" onclick="setAppStatus(${id}, 'rejected')" title="Отклонить"><svg width="14" height="14"><use href="#icon-x"/></svg></button>` : ''}
                    <button class="btn btn-ghost btn-sm" onclick="viewAppDetails(${id})" title="Подробнее"><svg width="14" height="14"><use href="#icon-eye"/></svg></button>
                `;
            }
            
            // Закрыть модалку если открыта
            if (currentAppId === id) {
                closeModal('appDetailsModal');
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
    const content = document.getElementById('appDetailsContent');
    
    content.innerHTML = '<div style="text-align:center;padding:2rem;"><div class="spinner"></div></div>';
    openModal('appDetailsModal');
    
    try {
        const res = await fetch(`/api/admin/application/${id}`, {
            headers: { 'X-CSRF-Token': csrf }
        });
        const data = await res.json();
        
        if (data.success && data.application) {
            const app = data.application;
            const avatarColors = (app.avatar_bg_value || '#3b82f6,#8b5cf6').split(',');
            const avatarStyle = `background:linear-gradient(135deg,${avatarColors[0]},${avatarColors[1] || avatarColors[0]})`;
            
            content.innerHTML = `
                <div class="app-detail">
                    <div class="app-detail-header">
                        <a href="/@${app.login}" class="admin-user-link">
                            <div class="admin-avatar" style="${!app.avatar ? avatarStyle : ''}">
                                ${app.avatar ? `<img src="${app.avatar}" alt="">` : app.username.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="admin-username">${app.username}</div>
                                <div class="admin-login">@${app.login}</div>
                            </div>
                        </a>
                        <span class="app-status status-${app.status}">${
                            app.status === 'pending' ? 'Ожидает' : 
                            app.status === 'accepted' ? 'Одобрена' : 'Отклонена'
                        }</span>
                    </div>
                    <div class="app-detail-body">
                        <p class="app-detail-message">${app.message}</p>
                    </div>
                    ${app.images && app.images.length ? `
                        <div class="app-detail-images" style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;">
                            ${app.images.map(img => `<img src="${img.image_path}" style="max-width:150px;border-radius:6px;">`).join('')}
                        </div>
                    ` : ''}
                    <div class="app-detail-meta">
                        <span>Создана: ${new Date(app.created_at).toLocaleString('ru')}</span>
                    </div>
                </div>
            `;
            
            // Обновляем кнопки в футере
            document.getElementById('modalAcceptBtn').onclick = () => setAppStatus(id, 'accepted');
            document.getElementById('modalRejectBtn').onclick = () => setAppStatus(id, 'rejected');
            document.getElementById('modalAcceptBtn').style.display = app.status !== 'accepted' ? '' : 'none';
            document.getElementById('modalRejectBtn').style.display = app.status !== 'rejected' ? '' : 'none';
        } else {
            content.innerHTML = '<p style="color:var(--danger)">Ошибка загрузки</p>';
        }
    } catch (err) {
        content.innerHTML = '<p style="color:var(--danger)">Ошибка сети</p>';
    }
}
</script>
