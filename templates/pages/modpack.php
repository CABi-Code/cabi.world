<?php
/**
 * @var string $platform
 * @var string $slug
 * @var array|null $modpack
 * @var array|null $user
 */

use React\EventLoop\Loop;
use React\Http\Browser;
use App\Repository\ModpackRepository;
use App\Repository\ApplicationRepository;

$config = require CONFIG_PATH . '/app.php';
$modpackRepo = new ModpackRepository();
$appRepo = new ApplicationRepository();

$modpack = $modpackRepo->findBySlug($platform, $slug);
$isLoading = false;

if (!$modpack) {
    // Модпак ещё не в базе - покажем загрузку и загрузим с API
    $isLoading = true;
    $browser = new Browser();
    $apiData = null;
    
    if ($platform === 'modrinth') {
        $browser->get("https://api.modrinth.com/v2/project/{$slug}", ['User-Agent' => 'CabiWorld/1.0'])->then(
            function ($response) use (&$apiData) {
                $apiData = json_decode((string)$response->getBody(), true);
            },
            function ($e) {}
        );
    } else {
        $apiKey = $config['curseforge_api_key'];
        if ($apiKey) {
            $browser->get("https://api.curseforge.com/v1/mods/search?gameId=432&slug={$slug}", [
                'User-Agent' => 'CabiWorld/1.0', 'x-api-key' => $apiKey
            ])->then(
                function ($response) use (&$apiData) {
                    $data = json_decode((string)$response->getBody(), true);
                    $apiData = $data['data'][0] ?? null;
                },
                function ($e) {}
            );
        }
    }
    
    Loop::run();
    
    if ($apiData) {
        $mpData = $platform === 'modrinth' ? [
            'external_id' => $apiData['id'], 'slug' => $apiData['slug'], 'name' => $apiData['title'],
            'description' => $apiData['description'], 'icon_url' => $apiData['icon_url'] ?? null,
            'author' => $apiData['team'] ?? 'Unknown', 'downloads' => $apiData['downloads'] ?? 0,
            'follows' => $apiData['followers'] ?? 0, 'external_url' => "https://modrinth.com/modpack/{$apiData['slug']}"
        ] : [
            'external_id' => (string)$apiData['id'], 'slug' => $apiData['slug'], 'name' => $apiData['name'],
            'description' => $apiData['summary'] ?? '', 'icon_url' => $apiData['logo']['thumbnailUrl'] ?? null,
            'author' => $apiData['authors'][0]['name'] ?? 'Unknown', 'downloads' => $apiData['downloadCount'] ?? 0,
            'follows' => 0, 'external_url' => "https://www.curseforge.com/minecraft/modpacks/{$apiData['slug']}"
        ];
        $modpack = $modpackRepo->getOrCreate($platform, $mpData);
        $isLoading = false;
    }
}

$currentUserId = $user['id'] ?? null;
$applications = $modpack ? $appRepo->findByModpack($modpack['id'], $currentUserId, 20) : [];
$applicationCount = $modpack ? $appRepo->countByModpack($modpack['id']) : 0;
$userApplication = $modpack && $user ? $appRepo->getUserApplication($modpack['id'], $user['id']) : null;

// Максимальная дата актуальности (1 месяц)
$maxRelevantDate = date('Y-m-d', strtotime('+31 days'));
$defaultRelevantDate = date('Y-m-d', strtotime('+14 days'));
?>

<?php if (!$modpack): ?>
    <div class="alert alert-error">Модпак не найден</div>
<?php else: ?>
<div class="modpack-page">
    <div class="mp-header" style="display:flex;gap:1.25rem;background:var(--surface);border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
        <?php if ($modpack['icon_url']): ?>
            <img src="<?= e($modpack['icon_url']) ?>" alt="" style="width:100px;height:100px;border-radius:8px;object-fit:cover;flex-shrink:0;">
        <?php endif; ?>
        <div style="flex:1;">
            <h1 style="font-size:1.375rem;margin-bottom:0.25rem;"><?= e($modpack['name']) ?></h1>
            <p style="color:var(--text-secondary);margin-bottom:0.5rem;">Автор: <?= e($modpack['author']) ?></p>
            <div style="display:flex;gap:1rem;margin-bottom:0.75rem;font-size:0.875rem;color:var(--text-secondary);">
                <span><svg width="14" height="14" style="vertical-align:-2px;"><use href="#icon-download"/></svg> <?= number_format($modpack['downloads']) ?></span>
                <?php if ($modpack['follows'] > 0): ?>
                    <span><svg width="14" height="14" style="vertical-align:-2px;"><use href="#icon-heart"/></svg> <?= number_format($modpack['follows']) ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= e($modpack['external_url']) ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">
                <svg width="14" height="14"><use href="#icon-external"/></svg>
                <?= $platform === 'modrinth' ? 'Modrinth' : 'CurseForge' ?>
            </a>
        </div>
    </div>
    
    <?php if ($modpack['description']): ?>
        <div style="background:var(--surface);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
            <h2 style="font-size:1rem;margin-bottom:0.5rem;">Описание</h2>
            <p style="color:var(--text-secondary);line-height:1.6;"><?= nl2br(e($modpack['description'])) ?></p>
        </div>
    <?php endif; ?>
    
    <div style="background:var(--surface);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;border:1px solid var(--border);">
        <h2 style="font-size:1rem;margin-bottom:0.75rem;">Оставить заявку</h2>
        <?php if (!$user): ?>
            <p style="color:var(--text-secondary);"><a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>, чтобы оставить заявку.</p>
        <?php elseif ($userApplication): ?>
            <div style="background:var(--primary-light);border-radius:6px;padding:1rem;border:1px solid rgba(59,130,246,0.2);">
                <h4 style="font-size:0.875rem;margin-bottom:0.5rem;color:var(--text-secondary);">Ваша заявка</h4>
                <div class="app-card <?= $userApplication['status'] === 'pending' ? 'pending' : '' ?>" style="background:var(--surface);">
                    <span class="app-status status-<?= $userApplication['status'] ?>">
                        <?= match($userApplication['status']) { 'pending'=>'На рассмотрении', 'accepted'=>'Одобрена', 'rejected'=>'Отклонена', default=>$userApplication['status'] } ?>
                    </span>
                    <p style="margin:0.5rem 0;line-height:1.5;"><?= nl2br(e($userApplication['message'])) ?></p>
                    <?php if ($userApplication['relevant_until']): ?>
                        <p style="font-size:0.8125rem;color:var(--text-muted);margin-bottom:0.5rem;">
                            Актуально до: <?= date('d.m.Y', strtotime($userApplication['relevant_until'])) ?>
                        </p>
                    <?php endif; ?>
                    <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
                        <button class="btn btn-secondary btn-sm" data-modal="editMyAppModal">
                            <svg width="12" height="12"><use href="#icon-edit"/></svg>Редактировать
                        </button>
                        <button class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="deleteMyApp(<?= $userApplication['id'] ?>)">
                            <svg width="12" height="12"><use href="#icon-trash"/></svg>Удалить
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <form id="applicationForm">
                <input type="hidden" name="modpack_id" value="<?= $modpack['id'] ?>">
                <div class="form-group">
                    <label class="form-label">Сообщение <span style="font-weight:400;color:var(--text-muted);">(макс. 2000 символов)</span></label>
                    <textarea name="message" class="form-input" rows="3" required placeholder="Расскажите о себе, опыте игры..." maxlength="2000"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Актуально до <span style="font-weight:400;color:var(--text-muted);">(макс. 1 месяц)</span></label>
                    <input type="date" name="relevant_until" class="form-input" value="<?= $defaultRelevantDate ?>" min="<?= date('Y-m-d') ?>" max="<?= $maxRelevantDate ?>">
                    <div class="form-hint">Влияет на сортировку в поиске. После этой даты заявка уйдёт вниз списка.</div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Discord</label>
                        <input type="text" name="discord" class="form-input" value="<?= e($user['discord'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telegram</label>
                        <input type="text" name="telegram" class="form-input" value="<?= e($user['telegram'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">VK</label>
                        <input type="text" name="vk" class="form-input" value="<?= e($user['vk'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg width="14" height="14"><use href="#icon-send"/></svg>Отправить заявку
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($applications)): ?>
    <div style="background:var(--surface);border-radius:8px;padding:1.25rem;border:1px solid var(--border);">
        <h2 style="font-size:1rem;margin-bottom:0.75rem;">Заявки (<?= $applicationCount ?>)</h2>
        <div class="app-list">
            <?php foreach ($applications as $app): ?>
                <?php 
                $isPending = $app['status'] === 'pending'; 
                $isOwnApp = $user && $app['user_id'] === $user['id']; 
                $images = $appRepo->getImages($app['id']);
                $isExpired = $app['relevant_until'] && strtotime($app['relevant_until']) < time();
                ?>
                <div class="app-card <?= $isPending ? 'pending' : '' ?>">
                    <div class="feed-user" style="margin-bottom:0.5rem;">
                        <a href="/profile/@<?= e($app['login']) ?>" class="feed-avatar">
                            <?php if ($app['avatar']): ?><img src="<?= e($app['avatar']) ?>" alt=""><?php else: ?><?= mb_strtoupper(mb_substr($app['username'], 0, 1)) ?><?php endif; ?>
                        </a>
                        <div>
                            <a href="/profile/@<?= e($app['login']) ?>" class="feed-name"><?= e($app['username']) ?></a>
                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></div>
                        </div>
                        <?php if ($isPending && $isOwnApp): ?><span class="app-status status-pending" style="margin-left:auto;">На рассмотрении</span><?php endif; ?>
                    </div>
                    <p style="line-height:1.6;margin-bottom:0.5rem;"><?= nl2br(e($app['message'])) ?></p>
                    
                    <?php if ($app['relevant_until']): ?>
                        <p style="font-size:0.8125rem;color:<?= $isExpired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin-bottom:0.5rem;">
                            <svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
                            <?= $isExpired ? 'Истёк:' : 'Актуально до:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($images)): ?>
                        <div style="display:flex;gap:0.5rem;margin:0.5rem 0;flex-wrap:wrap;">
                            <?php foreach ($images as $img): ?><a href="<?= e($img['image_path']) ?>" data-lightbox><img src="<?= e($img['image_path']) ?>" alt="" style="width:60px;height:60px;border-radius:4px;object-fit:cover;"></a><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="feed-contacts">
                        <?php if ($app['contact_discord']): ?><span class="contact-btn discord"><svg width="14" height="14"><use href="#icon-discord"/></svg><?= e($app['contact_discord']) ?></span><?php endif; ?>
                        <?php if ($app['contact_telegram']): ?><a href="https://t.me/<?= e(ltrim($app['contact_telegram'], '@')) ?>" class="contact-btn telegram" target="_blank"><svg width="14" height="14"><use href="#icon-telegram"/></svg><?= e($app['contact_telegram']) ?></a><?php endif; ?>
                        <?php if ($app['contact_vk']): ?><a href="https://vk.com/<?= e($app['contact_vk']) ?>" class="contact-btn vk" target="_blank"><svg width="14" height="14"><use href="#icon-vk"/></svg><?= e($app['contact_vk']) ?></a><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($user && $userApplication): ?>
<div id="editMyAppModal" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content">
        <h3>Редактировать заявку</h3>
        <div class="alert alert-warning" style="font-size:0.8125rem;">После редактирования заявка снова будет на рассмотрении</div>
        <form id="editMyAppForm">
            <input type="hidden" name="id" value="<?= $userApplication['id'] ?>">
            <div class="form-group">
                <label class="form-label">Сообщение</label>
                <textarea name="message" class="form-input" rows="3" required maxlength="2000"><?= e($userApplication['message']) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Актуально до</label>
                <input type="date" name="relevant_until" class="form-input" value="<?= $userApplication['relevant_until'] ?? $defaultRelevantDate ?>" min="<?= date('Y-m-d') ?>" max="<?= $maxRelevantDate ?>">
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Discord</label><input type="text" name="discord" class="form-input" value="<?= e($userApplication['contact_discord'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Telegram</label><input type="text" name="telegram" class="form-input" value="<?= e($userApplication['contact_telegram'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">VK</label><input type="text" name="vk" class="form-input" value="<?= e($userApplication['contact_vk'] ?? '') ?>"></div>
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

async function deleteMyApp(id) {
    if (!confirm('Удалить заявку?')) return;
    await fetch('/api/application/delete', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.dataset.modal).style.display = 'flex';
    });
});
document.querySelectorAll('.modal [data-close]').forEach(el => {
    el.addEventListener('click', () => el.closest('.modal').style.display = 'none');
});
</script>
<?php endif; ?>
<?php endif; ?>

<div id="lightbox" class="lightbox" style="display:none;">
    <button class="lightbox-close" data-close>&times;</button>
    <img src="" alt="" class="lightbox-img" id="lightboxImg">
</div>
