<div class="form-group">
    <label class="form-label">
        Сообщение 
        <span style="font-weight:400;color:var(--text-muted);">(макс. 2000 символов)</span>
    </label>
    <textarea 
        name="message" 
        class="form-input app-field-message" 
        rows="4" 
        required 
        maxlength="2000"
        placeholder="Расскажите о себе, своём опыте игры, что ищете..."
    ><?= e($appMessage) ?></textarea>
    <div class="form-hint">
        <span class="char-counter">0</span>/2000 символов
    </div>
</div>
