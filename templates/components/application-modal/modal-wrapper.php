<div id="<?= e($modalId) ?>" class="modal" style="display:none;">
    <div class="modal-overlay" data-close></div>
    <div class="modal-content modal-lg">
        <h3><?= $isEdit ? 'Редактировать заявку' : 'Оставить заявку на игру' ?></h3>
        
        <?php if ($isEdit): ?>
            <div class="alert alert-warning" style="font-size:0.8125rem;">
                После редактирования заявка снова будет отправлена на модерацию
            </div>
        <?php else: ?>
            <div class="alert alert-info" style="font-size:0.8125rem;background:rgba(59,130,246,0.1);color:var(--primary);border:1px solid rgba(59,130,246,0.2);">
                <svg width="14" height="14" style="vertical-align:-2px;margin-right:0.25rem;"><use href="#icon-info"/></svg>
                После отправки заявка будет проверена модератором и только потом опубликована
            </div>
        <?php endif; ?>
        
        <form id="<?= e($modalId) ?>Form" class="application-form" enctype="multipart/form-data">
            <input type="hidden" name="id" class="app-field-id" value="<?= e($appId) ?>">
            <?php if (!$isEdit && isset($modpackId)): ?>
                <input type="hidden" name="modpack_id" value="<?= (int)$modpackId ?>">
            <?php endif; ?>
            
            <?php include __DIR__ . '/form-message.php'; ?>
            
            <?php include __DIR__ . '/form-relevant-date.php'; ?>
            
            <?php include __DIR__ . '/form-contacts.php'; ?>
            
            <?php include __DIR__ . '/form-images.php'; ?>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <?= $isEdit ? 'Сохранить' : 'Отправить заявку' ?>
                </button>
            </div>
        </form>
    </div>
</div>
