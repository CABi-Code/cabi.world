<div class="toolbar-left">
	<span style="font-size:0.875rem;color:var(--text-secondary)">
		Заявок: <strong style="color:var(--text)"><?= number_format($totalCount) ?></strong>
	</span>
</div>
<div class="toolbar-right">
	<select class="sort-select" id="feedSortSelect">
		<option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>По дате</option>
		<option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>По популярности</option>
		<option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>По актуальности</option>
	</select>
</div>