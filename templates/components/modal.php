<?php
/**
 * Базовый компонент модального окна
 * 
 * @var string $modalId - ID модалки (обязательно)
 * @var string $modalTitle - Заголовок (опционально)
 * @var string $modalSize - Размер: sm, md, lg, xl (по умолчанию md)
 * @var string $modalContent - HTML контент (опционально, иначе используется yield)
 * @var bool $modalCloseButton - Показывать кнопку закрытия (по умолчанию true)
 */

$modalSize = $modalSize ?? 'md';
$modalCloseButton = $modalCloseButton ?? true;
?>

<div id="<?= e($modalId) ?>" class="modal" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-dialog modal-<?= e($modalSize) ?>">
        <div class="modal-content">
            <?php if (!empty($modalTitle) || $modalCloseButton): ?>
            <div class="modal-header">
                <?php if (!empty($modalTitle)): ?>
                    <h3 class="modal-title"><?= e($modalTitle) ?></h3>
                <?php endif; ?>
                <?php if ($modalCloseButton): ?>
                    <button type="button" class="modal-close" data-modal-close aria-label="Закрыть">
                        <svg width="20" height="20"><use href="#icon-x"/></svg>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="modal-body">
                <?= $modalContent ?? '' ?>
            </div>
            
            <?php if (!empty($modalFooter)): ?>
            <div class="modal-footer">
                <?= $modalFooter ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
