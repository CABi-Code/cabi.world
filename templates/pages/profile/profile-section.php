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
                    
                    <?php if (!empty($app['relevant_until'])): ?>
                        <?php $isExpired = strtotime($app['relevant_until']) < time(); ?>
                        <p style="font-size:0.8125rem;color:<?= $isExpired ? 'var(--danger)' : 'var(--text-muted)' ?>;margin:0.5rem 0;">
                            <svg width="12" height="12" style="vertical-align:-2px;"><use href="#icon-clock"/></svg>
                            <?= $isExpired ? 'Истёк:' : 'Актуально до:' ?> <?= date('d.m.Y', strtotime($app['relevant_until'])) ?>
                        </p>
                    <?php endif; ?>
                    
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
                                        onclick='openEditAppModal["editAppModal"](<?= e(json_encode($app)) ?>)'>
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