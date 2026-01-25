<div class="form-group">
    <label class="form-label">
        Актуально до 
        <span style="font-weight:400;color:var(--text-muted);">(обязательно, макс. 1 месяц)</span>
    </label>
    <input 
        type="date" 
        name="relevant_until" 
        class="form-input app-field-relevant" 
        value="<?= e($appRelevantUntil) ?>" 
        min="<?= $minRelevantDate ?>" 
        max="<?= $maxRelevantDate ?>" 
        required
    >
    <div class="form-hint">
        Влияет на сортировку в поиске. После этой даты заявка уйдёт вниз списка.
    </div>
</div>
