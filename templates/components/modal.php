<?php
/**
 * Базовый компонент модального окна
 * 
 * Использование:
 * <?php 
 * $modalId = 'myModal';
 * $modalTitle = 'Заголовок';
 * $modalSize = 'md'; // sm, md, lg, xl
 * ob_start();
 * ?>
 *   <!-- Контент модалки -->
 * <?php
 * $modalContent = ob_get_clean();
 * include TEMPLATES_PATH . '/components/modal.php';
 * ?>
 * 
 * @var string $modalId - ID модалки (обязательно)
 * @var string $modalTitle - Заголовок (опционально)
 * @var string $modalSize - Размер: sm, md, lg, xl (по умолчанию md)
 * @var string $modalContent - HTML контент тела
 * @var string $modalFooter - HTML контент футера (опционально)
 * @var bool $modalCloseButton - Показывать кнопку закрытия (по умолчанию true)
 */

$modalSize = $modalSize ?? 'md';
$modalCloseButton = $modalCloseButton ?? true;
?>
<div id="<?= e($modalId) ?>" class="modal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-<?= e($modalSize) ?>">
        <?php if (!empty($modalTitle) || $modalCloseButton): ?>
        <div class="modal-header">
            <?php if (!empty($modalTitle)): ?>
                <h3 class="modal-title"><?= $modalTitle ?></h3>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if ($modalCloseButton): ?>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
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
