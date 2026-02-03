<?php
/**
 * Строка поиска для модпак-селектора
 */
?>
<div class="modpack-selector-toolbar">
    <div class="modpack-selector-search">
        <svg width="16" height="16"><use href="#icon-search"/></svg>
        <input type="text" 
               id="modpackSelectorSearch" 
               placeholder="Поиск модпака..." 
               autocomplete="off">
    </div>
    
    <select class="modpack-selector-sort" id="modpackSelectorSort">
        <option value="downloads">По скачиваниям</option>
        <option value="applications">По заявкам</option>
    </select>
</div>
