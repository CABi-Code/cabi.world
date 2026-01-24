<div class="toolbar">
    <div class="toolbar-left">
        <span style="font-size:0.875rem;color:var(--text-secondary)">
            Найдено: <strong style="color:var(--text)"><?= number_format($totalHits) ?></strong>
        </span>
    </div>
    <div class="toolbar-right">
        <select class="sort-select" onchange="location.href='?sort='+this.value+'&page=1'">
            <option value="downloads" <?= $sort === 'downloads' ? 'selected' : '' ?>>По загрузкам</option>
            <option value="updated" <?= $sort === 'updated' ? 'selected' : '' ?>>По обновлению</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Новые</option>
		   <!-- <option value="name" <?//= $sort === 'name' ? 'selected' : '' ?>>По названию</option> -->
           <!-- <option value="follows" <?//= $sort === 'follows' ? 'selected' : '' ?>>По подписчикам</option> -->
        </select>
        <div class="view-toggle">
            <button class="view-btn <?= $view === 'grid' ? 'active' : '' ?>" data-view="grid" title="Сетка">
                <svg width="16" height="16"><use href="#icon-grid"/></svg>
            </button>
            <button class="view-btn <?= $view === 'compact' ? 'active' : '' ?>" data-view="compact" title="Компакт">
                <svg width="16" height="16"><use href="#icon-grid-small"/></svg>
            </button>
            <button class="view-btn <?= $view === 'list' ? 'active' : '' ?>" data-view="list" title="Список">
                <svg width="16" height="16"><use href="#icon-list"/></svg>
            </button>
        </div>
    </div>
</div>