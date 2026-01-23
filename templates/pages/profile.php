<?php
/**
 * @var array $profileUser
 * @var array|null $user
 * @var bool $isOwner
 */

use App\Repository\ApplicationRepository;

$appRepo = new ApplicationRepository();
$applications = $appRepo->findByUser($profileUser['id'], $isOwner, 20);

// Цвета профиля
$bannerBg = $profileUser['banner_bg_value'] ?? '#3b82f6,#8b5cf6';
$avatarBg = $profileUser['avatar_bg_value'] ?? '#3b82f6,#8b5cf6';
$bannerColors = explode(',', $bannerBg);
$avatarColors = explode(',', $avatarBg);
$bannerStyle = $profileUser['banner'] 
    ? "background-image:url(" . e($profileUser['banner']) . ");background-size:cover;background-position:center" 
    : 'background:linear-gradient(135deg,' . $bannerColors[0] . ',' . $bannerColors[1] ?? $bannerColors[0] . ')';
$avatarStyle = $profileUser['avatar'] 
    ? "" 
    : 'background:linear-gradient(135deg,' . $avatarColors[0] . ',' . $avatarColors[1] ?? $avatarColors[0] . ')';
?>

<div class="profile-banner" style="<?= $bannerStyle ?>">
    <?php if ($isOwner): ?>
        <button class="banner-edit-btn" id="bannerEditBtn">
            <svg width="14" height="14"><use href="#icon-camera"/></svg>
            <?= $profileUser['banner'] ? 'Изменить' : 'Добавить' ?>
        </button>
        <input type="file" id="bannerInput" accept="image/*" hidden>
    <?php endif; ?>
</div>

<div class="profile-header">
    <div class="profile-avatar-wrap">
        <div class="profile-avatar" style="<?= $avatarStyle ?>">
            <?php if ($profileUser['avatar']): ?>
                <img src="<?= e($profileUser['avatar']) ?>" alt="">
            <?php else: ?>
                <?= mb_strtoupper(mb_substr($profileUser['username'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <?php if ($isOwner): ?>
            <button class="avatar-edit-btn" id="avatarEditBtn">
                <svg width="12" height="12"><use href="#icon-camera"/></svg>
            </button>
            <input type="file" id="avatarInput" accept="image/*" hidden>
        <?php endif; ?>
    </div>
    
    <div class="profile-info">
        <h1 class="profile-name"><?= e($profileUser['username']) ?></h1>
        <p class="profile-login">@<?= e($profileUser['login']) ?></p>
        
        <?php if ($profileUser['bio']): ?>
            <p class="profile-bio"><?= nl2br(e($profileUser['bio'])) ?></p>
        <?php endif; ?>
        
        <?php if ($profileUser['discord'] || $profileUser['telegram'] || $profileUser['vk']): ?>
            <div class="profile-contacts">
                <?php if ($profileUser['discord']): ?>
                    <span class="contact-btn discord">
                        <svg width="14" height="14"><use href="#icon-discord"/></svg>
                        <?= e($profileUser['discord']) ?>
                    </span>
                <?php endif; ?>
                <?php if ($profileUser['telegram']): ?>
                    <a href="https://t.me/<?= e(ltrim($profileUser['telegram'], '@')) ?>" class="contact-btn telegram" target="_blank">
                        <svg width="14" height="14"><use href="#icon-telegram"/></svg>
                        <?= e($profileUser['telegram']) ?>
                    </a>
                <?php endif; ?>
                <?php if ($profileUser['vk']): ?>
                    <a href="https://vk.com/<?= e($profileUser['vk']) ?>" class="contact-btn vk" target="_blank">
                        <svg width="14" height="14"><use href="#icon-vk"/></svg>
                        <?= e($profileUser['vk']) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <p class="profile-date">На сайте с <?= date('d.m.Y', strtotime($profileUser['created_at'])) ?></p>
    </div>
    
    <?php if ($isOwner): ?>
        <a href="/settings" class="btn btn-secondary btn-sm">
            <svg width="14" height="14"><use href="#icon-edit"/></svg>
            Редактировать
        </a>
    <?php endif; ?>
</div>

<?php if (!empty($applications)): ?>
    <div class="profile-section">
        <h2 class="section-title"><?= $isOwner ? 'Мои заявки' : 'Заявки' ?></h2>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
                <?php
                $isPending = $app['status'] === 'pending';
                $isHidden = !empty($app['is_hidden']);
                $images = $appRepo->getImages($app['id']);
                $cardClass = 'app-card';
                if ($isPending) $cardClass .= ' pending';
                if ($isHidden) $cardClass .= ' hidden-app';
                ?>
                <div class="<?= $cardClass ?>">
                    <div class="app-header">
                        <?php if ($app['icon_url']): ?>
                            <img src="<?= e($app['icon_url']) ?>" alt="" class="app-icon">
                        <?php endif; ?>
                        <div style="flex:1;">
                            <a href="/modpack/<?= e($app['platform']) ?>/<?= e($app['slug']) ?>" class="app-modpack">
                                <?= e($app['modpack_name']) ?>
                            </a>
                            <div style="display:flex;gap:0.375rem;margin-top:0.25rem;flex-wrap:wrap;">
                                <span class="app-status status-<?= $app['status'] ?>">
                                    <?= match($app['status']) { 'pending'=>'На рассмотрении', 'accepted'=>'Одобрена', 'rejected'=>'Отклонена', default=>$app['status'] } ?>
                                </span>
                                <?php if ($isHidden): ?>
                                    <span style="font-size:0.6875rem;color:var(--text-muted);display:flex;align-items:center;gap:0.25rem;">
                                        <svg width="12" height="12"><use href="#icon-eye-off"/></svg>
                                        Скрыта
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <p class="app-message"><?= nl2br(e($app['message'])) ?></p>
                    
                    <?php if (!empty($images)): ?>
                        <div style="display:flex;gap:0.5rem;margin:0.5rem 0;flex-wrap:wrap;">
                            <?php foreach ($images as $img): ?>
                                <a href="<?= e($img['image_path']) ?>" data-lightbox>
                                    <img src="<?= e($img['image_path']) ?>" alt="" style="width:60px;height:60px;border-radius:4px;object-fit:cover;">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="app-footer">
                        <span class="app-date"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></span>
                        
                        <?php if ($isOwner): ?>
                            <div class="app-actions">
                                <button class="btn btn-ghost btn-icon btn-sm" title="<?= $isHidden ? 'Показать' : 'Скрыть' ?>"
                                        onclick="toggleHidden(<?= $app['id'] ?>)">
                                    <svg width="14" height="14"><use href="#icon-<?= $isHidden ? 'eye' : 'eye-off' ?>"/></svg>
                                </button>
                                <button class="btn btn-ghost btn-icon btn-sm" title="Редактировать"
                                        data-modal="editAppModal" data-app='<?= e(json_encode($app)) ?>'>
                                    <svg width="14" height="14"><use href="#icon-edit"/></svg>
                                </button>
                                <button class="btn btn-ghost btn-icon btn-sm" style="color:var(--danger)" title="Удалить"
                                        onclick="deleteApp(<?= $app['id'] ?>)">
                                    <svg width="14" height="14"><use href="#icon-trash"/></svg>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php elseif ($isOwner): ?>
    <div class="profile-section">
        <div style="text-align:center;padding:1.5rem;color:var(--text-secondary);">
            <p style="margin-bottom:0.75rem;">У вас пока нет заявок</p>
            <a href="/modrinth" class="btn btn-primary btn-sm">Найти модпак</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($isOwner): ?>
<!-- Image Editor Modal -->
<div id="imgEditorModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content">
        <h3 id="editorTitle">Редактировать</h3>
        <div class="img-editor">
            <div class="img-preview" id="editorPreview"></div>
            <div class="img-controls">
                <div class="zoom-range">
                    <svg width="14" height="14"><use href="#icon-zoom-out"/></svg>
                    <input type="range" id="zoomRange" min="1" max="3" step="0.05" value="1">
                    <svg width="14" height="14"><use href="#icon-zoom-in"/></svg>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
            <button type="button" class="btn btn-primary btn-sm" id="saveImgBtn">Сохранить</button>
        </div>
    </div>
</div>

<!-- Edit Application Modal -->
<div id="editAppModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content">
        <h3>Редактировать заявку</h3>
        <div class="alert alert-warning" style="font-size:0.8125rem;">После редактирования заявка снова будет на рассмотрении</div>
        <form id="editAppForm">
            <input type="hidden" name="id" id="editAppId">
            <div class="form-group">
                <label class="form-label">Сообщение</label>
                <textarea name="message" id="editAppMessage" class="form-input" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Discord</label>
                    <input type="text" name="discord" id="editAppDiscord" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Telegram</label>
                    <input type="text" name="telegram" id="editAppTelegram" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">VK</label>
                    <input type="text" name="vk" id="editAppVk" class="form-input">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
                <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

// Avatar/Banner upload
let currentUploadType = null;
let currentFile = null;
let cropData = { x: 0, y: 0, scale: 1 };

document.getElementById('avatarEditBtn')?.addEventListener('click', () => document.getElementById('avatarInput').click());
document.getElementById('bannerEditBtn')?.addEventListener('click', () => document.getElementById('bannerInput').click());

['avatar', 'banner'].forEach(type => {
    document.getElementById(type + 'Input')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        
        currentFile = file;
        currentUploadType = type;
        cropData = { x: 0, y: 0, scale: 1 };
        
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('editorTitle').textContent = type === 'avatar' ? 'Редактировать аватар' : 'Редактировать баннер';
            document.getElementById('editorPreview').innerHTML = `<img src="${ev.target.result}" id="cropImg" style="transform-origin:center;cursor:move;">`;
            document.getElementById('zoomRange').value = 1;
            document.getElementById('imgEditorModal').style.display = 'flex';
            setupCrop();
        };
        reader.readAsDataURL(file);
        this.value = '';
    });
});

function setupCrop() {
    const img = document.getElementById('cropImg');
    if (!img) return;
    let isDragging = false, startX, startY;
    
    img.onmousedown = e => { isDragging = true; startX = e.clientX - cropData.x; startY = e.clientY - cropData.y; };
    document.onmousemove = e => { if (isDragging) { cropData.x = e.clientX - startX; cropData.y = e.clientY - startY; updateCrop(); } };
    document.onmouseup = () => isDragging = false;
    
    document.getElementById('zoomRange').oninput = function() { cropData.scale = parseFloat(this.value); updateCrop(); };
}

function updateCrop() {
    const img = document.getElementById('cropImg');
    if (img) img.style.transform = `translate(${cropData.x}px, ${cropData.y}px) scale(${cropData.scale})`;
}

document.getElementById('saveImgBtn')?.addEventListener('click', async () => {
    if (!currentFile) return;
    const btn = document.getElementById('saveImgBtn');
    btn.disabled = true; btn.textContent = '...';
    
    const fd = new FormData();
    fd.append(currentUploadType, currentFile);
    
    const res = await fetch('/api/user/' + currentUploadType, { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd });
    const data = await res.json();
    if (data.success) location.reload();
    else { alert(data.error || 'Ошибка'); btn.disabled = false; btn.textContent = 'Сохранить'; }
});

// Application actions
async function toggleHidden(id) {
    await fetch('/api/application/toggle-hidden', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

async function deleteApp(id) {
    if (!confirm('Удалить заявку?')) return;
    await fetch('/api/application/delete', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

// Edit modal
document.querySelectorAll('[data-modal="editAppModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const app = JSON.parse(btn.dataset.app);
        document.getElementById('editAppId').value = app.id;
        document.getElementById('editAppMessage').value = app.message || '';
        document.getElementById('editAppDiscord').value = app.contact_discord || '';
        document.getElementById('editAppTelegram').value = app.contact_telegram || '';
        document.getElementById('editAppVk').value = app.contact_vk || '';
        document.getElementById('editAppModal').style.display = 'flex';
    });
});

// Modal close
document.querySelectorAll('.modal [data-close]').forEach(el => {
    el.addEventListener('click', () => el.closest('.modal').style.display = 'none');
});
</script>
<?php endif; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>