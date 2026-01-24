// image-editor/ModalUI.js - Creates and manages the editor modal UI

export class ModalUI {
    constructor() {
        this.modal = null;
        this.elements = {};
    }
    
    create() {
        // Remove existing modal if any
        document.getElementById('advancedImgEditor')?.remove();
        
        const modal = document.createElement('div');
        modal.id = 'advancedImgEditor';
        modal.className = 'modal img-editor-modal';
        modal.style.display = 'none';
        modal.innerHTML = this.getTemplate();
        
        document.body.appendChild(modal);
        this.modal = modal;
        
        // Cache element references
        this.elements = {
            title: modal.querySelector('#editorTitle'),
            canvas: modal.querySelector('#editorCanvas'),
            imageWrapper: modal.querySelector('#imageWrapper'),
            image: modal.querySelector('#editorImage'),
            cropOverlay: modal.querySelector('#cropOverlay'),
            cropArea: modal.querySelector('#cropArea'),
            saveBtn: modal.querySelector('#editorSaveBtn'),
            darkTop: modal.querySelector('#darkTop'),
            darkRight: modal.querySelector('#darkRight'),
            darkBottom: modal.querySelector('#darkBottom'),
            darkLeft: modal.querySelector('#darkLeft')
        };
        
        return modal;
    }
    
    getTemplate() {
        return `
            <div class="modal-overlay" data-close></div>
            <div class="modal-content">
                <h3 id="editorTitle">Редактировать изображение</h3>
                
                <div class="img-editor-container">
                    <div class="img-editor-main">
                        <div class="img-editor-canvas" id="editorCanvas">
                            <div class="img-editor-image-wrapper" id="imageWrapper">
                                <img src="" alt="" class="img-editor-image" id="editorImage">
                                <div class="crop-overlay" id="cropOverlay">
                                    <div class="crop-darkener" id="darkTop"></div>
                                    <div class="crop-darkener" id="darkRight"></div>
                                    <div class="crop-darkener" id="darkBottom"></div>
                                    <div class="crop-darkener" id="darkLeft"></div>
                                    <div class="crop-area" id="cropArea">
                                        <div class="crop-circle"></div>
                                        <div class="crop-handle tl" data-handle="tl"></div>
                                        <div class="crop-handle tr" data-handle="tr"></div>
                                        <div class="crop-handle bl" data-handle="bl"></div>
                                        <div class="crop-handle br" data-handle="br"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="img-editor-preview-panel" id="previewPanel">
                        <h4>Предпросмотр</h4>
                        
                        <!-- Avatar previews -->
                        <div class="preview-item avatar-preview" id="avatarPreviewLargeWrap">
                            <div class="preview-label">На странице профиля</div>
                            <div class="preview-avatar">
                                <img src="" alt="" id="previewAvatarLarge">
                            </div>
                        </div>
                        <div class="preview-item avatar-preview" id="avatarPreviewSmallWrap">
                            <div class="preview-label">В комментариях</div>
                            <div class="preview-avatar preview-avatar-small">
                                <img src="" alt="" id="previewAvatarSmall">
                            </div>
                        </div>
                        
                        <!-- Banner previews -->
                        <div class="preview-item banner-preview" id="bannerPreviewDesktopWrap" style="display:none;">
                            <div class="preview-label">На компьютере</div>
                            <div class="preview-banner preview-banner-desktop">
                                <img src="" alt="" id="previewBannerDesktop">
                            </div>
                        </div>
                        <div class="preview-item banner-preview" id="bannerPreviewMobileWrap" style="display:none;">
                            <div class="preview-label">На телефоне</div>
                            <div class="preview-banner preview-banner-mobile">
                                <img src="" alt="" id="previewBannerMobile">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="img-editor-controls">
                    <p class="img-editor-tip">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>
                        </svg>
                        Перетащите область или измените размер за углы
                    </p>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary btn-sm" data-close>Отмена</button>
                    <button type="button" class="btn btn-primary btn-sm" id="editorSaveBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Сохранить
                    </button>
                </div>
            </div>
        `;
    }
    
    show() {
        this.modal.style.display = 'flex';
    }
    
    hide() {
        this.modal.style.display = 'none';
    }
    
    setTitle(title) {
        this.elements.title.textContent = title;
    }
    
    setCropMode(type) {
        this.elements.cropArea.classList.toggle('avatar-mode', type === 'avatar');
    }
    
    setImageSrc(src) {
        this.elements.image.src = src;
    }
    
    updateWrapperSize(width, height) {
        // Set wrapper and overlay to exact image display size
        const wrapper = this.elements.imageWrapper;
        const overlay = this.elements.cropOverlay;
        
        wrapper.style.width = width + 'px';
        wrapper.style.height = height + 'px';
        
        overlay.style.width = width + 'px';
        overlay.style.height = height + 'px';
    }
    
    updateDarkeners(crop, displayWidth, displayHeight) {
        const { x, y, width, height } = crop;
        
        // Clamp values to valid range
        const safeX = Math.max(0, Math.min(x, displayWidth));
        const safeY = Math.max(0, Math.min(y, displayHeight));
        const safeW = Math.max(0, Math.min(width, displayWidth - safeX));
        const safeH = Math.max(0, Math.min(height, displayHeight - safeY));
        
        const bottomH = Math.max(0, displayHeight - safeY - safeH);
        const rightW = Math.max(0, displayWidth - safeX - safeW);
        
        this.elements.darkTop.style.cssText = `top:0;left:0;right:0;height:${safeY}px`;
        this.elements.darkBottom.style.cssText = `bottom:0;left:0;right:0;height:${bottomH}px`;
        this.elements.darkLeft.style.cssText = `top:${safeY}px;left:0;width:${safeX}px;height:${safeH}px`;
        this.elements.darkRight.style.cssText = `top:${safeY}px;right:0;width:${rightW}px;height:${safeH}px`;
    }
    
    updateCropAreaPosition(crop) {
        const cropArea = this.elements.cropArea;
        cropArea.style.left = crop.x + 'px';
        cropArea.style.top = crop.y + 'px';
        cropArea.style.width = crop.width + 'px';
        cropArea.style.height = crop.height + 'px';
    }
    
    setSaveButtonLoading(loading) {
        const btn = this.elements.saveBtn;
        if (loading) {
            btn._originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Сохранение...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn._originalHtml || 'Сохранить';
        }
    }
}
