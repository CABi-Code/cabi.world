<?php use App\Repository\ApplicationRepository; ?>
<div class="form-group">
    <label class="form-label">
        Сообщение 
        <span style="font-weight:400;color:var(--text-muted);">(макс. <span class="max-chars"><?= ApplicationRepository::MAX_MESSAGE_LENGTH ?></span> символов)</span>
    </label>
    <textarea 
        name="message" 
        class="form-input app-field-message" 
        rows="4" 
        required 
        maxlength="<?= ApplicationRepository::MAX_MESSAGE_LENGTH ?>"
        placeholder="Расскажите о себе, своём опыте игры, что ищете..."
    ><?= e($appMessage) ?></textarea>
    <div class="form-hint">
        <span class="char-counter">0</span>/<span class="max-chars"><?= ApplicationRepository::MAX_MESSAGE_LENGTH ?></span> символов
    </div>
</div>
