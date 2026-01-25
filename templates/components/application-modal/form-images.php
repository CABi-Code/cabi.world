<?php use App\Repository\ApplicationRepository; ?>
<div class="form-group">
    <label class="form-label">
        Изображения 
        <span style="font-weight:400;color:var(--text-muted);">(необязательно, до <?= ApplicationRepository::MAX_IMAGES ?> штук)</span>
    </label>
    
    <div class="images-upload-area">
        <div class="images-preview" id="<?= e($modalId) ?>ImagesPreview">
            <!-- Превью изображений будут добавляться сюда через JS -->
        </div>
        
        <label class="image-upload-btn" id="<?= e($modalId) ?>ImageUploadBtn">
            <input 
                type="file" 
                name="images[]" 
                class="app-field-images" 
                accept="image/jpeg,image/png,image/gif,image/webp"
                multiple
                hidden
            >
            <svg width="20" height="20"><use href="#icon-image"/></svg>
            <span>Добавить фото</span>
        </label>
    </div>
    
    <div class="form-hint">
        JPG, PNG, GIF, WebP. Максимум 5 МБ на файл.
    </div>
</div>
