
<?php $count_maxlength = 5; ?>

<div class="form-group">
    <label class="form-label">
        Сообщение 
        <span style="font-weight:400;color:var(--text-muted);">(макс. <?php echo $count_maxlength; ?> символов)</span>
    </label>
    <textarea 
        name="message" 
        class="form-input app-field-message" 
        rows="4" 
        required 
        maxlength="<?php echo $count_maxlength; ?>"
        placeholder="Расскажите о себе, своём опыте игры, что ищете..."
    ><?= e($appMessage) ?></textarea>
    <div class="form-hint">
        <span class="char-counter">0</span>/<?php echo $count_maxlength; ?> символов
    </div>
</div>
