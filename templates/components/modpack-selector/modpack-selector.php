<?php
/**
 * Глобальный компонент выбора модпаков
 * Загружается через AJAX и может использоваться в любом месте сайта
 */
?>
<div class="modal modpack-selector-modal" id="modpackSelectorModal" style="display:none;">
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Выберите модпак</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        
        <div class="modal-body">
            <?php require __DIR__ . '/search-bar.php'; ?>
            <?php require __DIR__ . '/modpack-list.php'; ?>
        </div>
        
        <div class="modal-footer">
            <button class="btn btn-secondary" data-modal-close>Отмена</button>
            <button class="btn btn-primary" id="modpackSelectorConfirm" disabled>Готово</button>
        </div>
    </div>
</div>
