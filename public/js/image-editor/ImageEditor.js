// image-editor/ImageEditor.js - Main editor class

import { ModalUI } from './ModalUI.js';
import { CropHandler } from './CropHandler.js';
import { PreviewManager } from './PreviewManager.js';

export class ImageEditor {
    constructor(options = {}) {
        this.csrf = options.csrf || '';
        this.onSave = options.onSave || null;
        
        // State
        this.type = 'avatar';
        this.file = null;
        this.crop = { x: 0, y: 0, width: 200, height: 200 };
        
        // Image dimensions
        this.imgWidth = 0;
        this.imgHeight = 0;
        this.displayWidth = 0;
        this.displayHeight = 0;
        
        // Components
        this.ui = new ModalUI();
        this.cropHandler = new CropHandler(this);
        this.previewManager = new PreviewManager(this);
        
        this.init();
    }
    
    init() {
        const modal = this.ui.create();
        this.image = this.ui.elements.image;
        
        // Initialize sub-components
        this.cropHandler.init(this.ui.elements.cropArea);
        this.previewManager.init(modal);
        
        this.bindEvents();
    }
    
    bindEvents() {
        const modal = this.ui.modal;
        
        // Close modal
        modal.querySelectorAll('[data-close]').forEach(el => {
            el.addEventListener('click', () => this.close());
        });
        
        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                this.close();
            }
        });
        
        // Save button
        this.ui.elements.saveBtn.addEventListener('click', () => this.save());
        
        // Image load
        this.image.addEventListener('load', () => this.onImageLoad());
    }
    
    open(file, type = 'avatar') {
        this.file = file;
        this.type = type;
        
        // Update UI
        this.ui.setTitle(type === 'avatar' ? 'Редактировать аватар' : 'Редактировать баннер');
        this.ui.setCropMode(type);
        this.previewManager.showForType(type);
        
        // Load image
        const reader = new FileReader();
        reader.onload = (e) => {
            this.ui.setImageSrc(e.target.result);
        };
        reader.readAsDataURL(file);
        
        this.ui.show();
    }
    
    close() {
        this.ui.hide();
        this.file = null;
    }
    
    onImageLoad() {
        // Get actual image dimensions
        this.imgWidth = this.image.naturalWidth;
        this.imgHeight = this.image.naturalHeight;
        
        // Calculate display dimensions to fit in canvas
        // The image should be scaled to fit, maintaining aspect ratio
        const canvas = this.ui.elements.canvas;
        const maxWidth = canvas.clientWidth || 500;
        const maxHeight = 400;
        
        const imgAspect = this.imgWidth / this.imgHeight;
        
        let displayWidth, displayHeight;
        
        if (this.imgWidth <= maxWidth && this.imgHeight <= maxHeight) {
            // Image fits, use natural size
            displayWidth = this.imgWidth;
            displayHeight = this.imgHeight;
        } else if (imgAspect > maxWidth / maxHeight) {
            // Image is wider than container
            displayWidth = maxWidth;
            displayHeight = maxWidth / imgAspect;
        } else {
            // Image is taller than container
            displayHeight = maxHeight;
            displayWidth = maxHeight * imgAspect;
        }
        
        this.displayWidth = displayWidth;
        this.displayHeight = displayHeight;
        
        // Set image display size explicitly
        this.image.style.width = displayWidth + 'px';
        this.image.style.height = displayHeight + 'px';
        
        // Update wrapper and overlay to match
        this.ui.updateWrapperSize(displayWidth, displayHeight);
        
        // Initialize crop area after dimensions are set
        requestAnimationFrame(() => {
            this.cropHandler.initCropArea();
            this.updateCropOverlay();
            this.updatePreviews();
        });
    }
    
    updateCropOverlay() {
        this.ui.updateCropAreaPosition(this.crop);
        this.ui.updateDarkeners(this.crop, this.displayWidth, this.displayHeight);
    }
    
    updatePreviews() {
        this.previewManager.update();
    }
    
    async save() {
        this.ui.setSaveButtonLoading(true);
        
        try {
            // Calculate scale from display to natural
            const scaleX = this.imgWidth / this.displayWidth;
            const scaleY = this.imgHeight / this.displayHeight;
            
            // Natural coordinates
            const cropData = {
                x: Math.round(Math.max(0, this.crop.x * scaleX)),
                y: Math.round(Math.max(0, this.crop.y * scaleY)),
                width: Math.round(this.crop.width * scaleX),
                height: Math.round(this.crop.height * scaleY)
            };
            
            const formData = new FormData();
            formData.append(this.type, this.file);
            formData.append('crop_x', cropData.x);
            formData.append('crop_y', cropData.y);
            formData.append('crop_width', cropData.width);
            formData.append('crop_height', cropData.height);
            
            const endpoint = this.type === 'avatar' ? '/api/user/avatar' : '/api/user/banner';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-Token': this.csrf },
                body: formData
            });
            
            const data = await res.json();
            
            if (data.success) {
                if (this.onSave) {
                    this.onSave(data);
                } else {
                    location.reload();
                }
            } else {
                alert(data.error || 'Ошибка сохранения');
                this.ui.setSaveButtonLoading(false);
            }
        } catch (err) {
            alert('Ошибка сети');
            this.ui.setSaveButtonLoading(false);
        }
    }
}
