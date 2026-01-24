// image-editor/PreviewManager.js - Handles preview rendering

export class PreviewManager {
    constructor(editor) {
        this.editor = editor;
        this.previewElements = {};
    }
    
    init(modal) {
        this.modal = modal;
        this.previewElements = {
            avatarLarge: modal.querySelector('#previewAvatarLarge'),
            avatarSmall: modal.querySelector('#previewAvatarSmall'),
            bannerDesktop: modal.querySelector('#previewBannerDesktop'),
            bannerMobile: modal.querySelector('#previewBannerMobile')
        };
    }
    
    showForType(type) {
        // Toggle preview panels
        this.modal.querySelectorAll('.avatar-preview').forEach(el => {
            el.style.display = type === 'avatar' ? 'block' : 'none';
        });
        this.modal.querySelectorAll('.banner-preview').forEach(el => {
            el.style.display = type === 'banner' ? 'block' : 'none';
        });
    }
    
    update() {
        const { crop, imgWidth, imgHeight, displayWidth, displayHeight, type, image } = this.editor;
        const { x, y, width, height } = crop;
        
        // Calculate scale from display to natural
        const scaleX = imgWidth / displayWidth;
        const scaleY = imgHeight / displayHeight;
        
        // Natural coordinates (clamped to image bounds)
        const nx = Math.max(0, Math.round(x * scaleX));
        const ny = Math.max(0, Math.round(y * scaleY));
        const nw = Math.min(imgWidth - nx, Math.round(width * scaleX));
        const nh = Math.min(imgHeight - ny, Math.round(height * scaleY));
        
        // Create preview canvas
        const previewCanvas = document.createElement('canvas');
        const ctx = previewCanvas.getContext('2d');
        
        if (type === 'avatar') {
            this.renderAvatarPreviews(ctx, previewCanvas, image, nx, ny, nw, nh);
        } else {
            this.renderBannerPreviews(ctx, previewCanvas, image, nx, ny, nw, nh);
        }
    }
    
    renderAvatarPreviews(ctx, canvas, image, nx, ny, nw, nh) {
        // Square output for avatar
        const outputSize = 256;
        canvas.width = outputSize;
        canvas.height = outputSize;
        
        ctx.drawImage(image, nx, ny, nw, nh, 0, 0, outputSize, outputSize);
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        this.previewElements.avatarLarge.src = dataUrl;
        this.previewElements.avatarSmall.src = dataUrl;
    }
    
    renderBannerPreviews(ctx, canvas, image, nx, ny, nw, nh) {
        // Banner maintains aspect ratio from crop
        const aspectRatio = nw / nh;
        
        // Output dimensions
        const outputWidth = 1200;
        const outputHeight = Math.round(outputWidth / aspectRatio);
        
        canvas.width = outputWidth;
        canvas.height = outputHeight;
        
        ctx.drawImage(image, nx, ny, nw, nh, 0, 0, outputWidth, outputHeight);
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        this.previewElements.bannerDesktop.src = dataUrl;
        this.previewElements.bannerMobile.src = dataUrl;
    }
}
