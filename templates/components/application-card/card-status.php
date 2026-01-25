<?php if ($isOwner): ?>
    <div style="display:flex;gap:0.375rem;margin-bottom:0.5rem;flex-wrap:wrap;">
        <span class="app-status status-<?= $app['status'] ?>">
            <?= match($app['status']) { 
                'pending' => 'На рассмотрении', 
                'accepted' => 'Одобрена', 
                'rejected' => 'Отклонена', 
                default => $app['status'] 
            } ?>
        </span>
        <?php if ($isHidden): ?>
            <span style="font-size:0.6875rem;color:var(--text-muted);display:flex;align-items:center;gap:0.25rem;">
                <svg width="12" height="12"><use href="#icon-eye-off"/></svg>
                Скрыта
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
