<?php
/**
 * Временная кнопка для тестирования модпак-селектора
 * Добавить на главную страницу: <?php include_once TEMPLATES_PATH . '/components/modpack-selector/demo-button.php'; ?>
 */
?>
<div class="modpack-selector-demo" style="margin-bottom: 1.5rem;">
    <div style="padding: 1.25rem; background: var(--surface); border-radius: 10px; border: 1px solid var(--border);">
        <h3 style="font-size: 1rem; margin-bottom: 0.75rem;">Тест выбора модпака</h3>
        
        <div id="selectedModpackPreview" style="display: none; margin-bottom: 1rem;">
            <div class="selected-modpack-card" id="selectedModpackCard"></div>
        </div>
        
        <button type="button" class="btn btn-primary" id="openModpackSelectorBtn">
            <svg width="16" height="16"><use href="#icon-package"/></svg>
            Выбрать модпак
        </button>
    </div>
</div>

<style>
.selected-modpack-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--bg);
    border-radius: 8px;
    border: 1px solid var(--primary);
}
.selected-modpack-card .mp-icon { width: 40px; height: 40px; border-radius: 6px; overflow: hidden; flex-shrink: 0; background: var(--surface-hover); display: flex; align-items: center; justify-content: center; }
.selected-modpack-card .mp-icon img { width: 100%; height: 100%; object-fit: cover; }
.selected-modpack-card .mp-info { flex: 1; min-width: 0; }
.selected-modpack-card .mp-name { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.selected-modpack-card .mp-meta { font-size: 0.8125rem; color: var(--text-secondary); }
</style>

<?php require __DIR__ . '/demo-button-js.php'; ?>
