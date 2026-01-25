<?php if (!$user): ?>
    <p style="color:var(--text-secondary);">
        <a href="/login">Войдите</a> или <a href="/register">зарегистрируйтесь</a>, чтобы оставить заявку.
    </p>
<?php elseif ($userApplication): ?>
    <div style="background:var(--primary-light);border-radius:6px;padding:1rem;border:1px solid rgba(59,130,246,0.2);">
        <h4 style="font-size:0.875rem;margin-bottom:0.5rem;color:var(--text-secondary);">Ваша заявка</h4>
        <div class="app-card <?= $userApplication['status'] === 'pending' ? 'pending' : '' ?>" style="background:var(--surface);">
            <span class="app-status status-<?= $userApplication['status'] ?>">
                <?= match($userApplication['status']) { 
                    'pending'=>'На рассмотрении', 
                    'accepted'=>'Одобрена', 
                    'rejected'=>'Отклонена', 
                    default=>$userApplication['status'] 
                } ?>
            </span>
            <p style="margin:0.5rem 0;line-height:1.5;"><?= nl2br(e($userApplication['message'])) ?></p>
            <?php if ($userApplication['relevant_until']): ?>
                <p style="font-size:0.8125rem;color:var(--text-muted);margin-bottom:0.5rem;">
                    Актуально до: <?= date('d.m.Y', strtotime($userApplication['relevant_until'])) ?>
                </p>
            <?php endif; ?>
            <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
                <button class="btn btn-secondary btn-sm" onclick="openApplicationModal['editMyAppModal'](<?= e(json_encode($userApplication)) ?>)">
                    <svg width="12" height="12"><use href="#icon-edit"/></svg>Редактировать
                </button>
                <button class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="deleteMyApp(<?= $userApplication['id'] ?>)">
                    <svg width="12" height="12"><use href="#icon-trash"/></svg>Удалить
                </button>
            </div>
        </div>
    </div>
<?php else: ?>
    <button 
        type="button" 
        class="btn btn-primary btn-lg" 
        onclick="openApplicationModal['createAppModal']()"
        style="width:100%;"
    >
        <svg width="16" height="16"><use href="#icon-send"/></svg>
        Оставить заявку на игру
    </button>
<?php endif; ?>
