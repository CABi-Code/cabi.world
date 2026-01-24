// image-editor/CropHandler.js - Handles crop area dragging and resizing

export class CropHandler {
    constructor(editor) {
        this.editor = editor;
        
        // Drag state
        this.isDragging = false;
        this.isResizing = false;
        this.resizeHandle = null;
        this.startX = 0;
        this.startY = 0;
        this.startCrop = {};
        
        // Min size
        this.minSize = 60;
        
        // Fixed aspect ratios
        this.aspectRatios = {
            avatar: 1, // Square
            banner: 4  // 4:1 for banner
        };
    }
    
    init(cropArea) {
        this.cropArea = cropArea;
        this.bindEvents();
    }
    
    bindEvents() {
        // Crop area drag
        this.cropArea.addEventListener('mousedown', (e) => this.startDrag(e));
        this.cropArea.addEventListener('touchstart', (e) => this.startDrag(e), { passive: false });
        
        // Resize handles
        this.cropArea.querySelectorAll('.crop-handle').forEach(handle => {
            handle.addEventListener('mousedown', (e) => this.startResize(e));
            handle.addEventListener('touchstart', (e) => this.startResize(e), { passive: false });
        });
        
        // Global mouse/touch move and up
        document.addEventListener('mousemove', (e) => this.onMove(e));
        document.addEventListener('mouseup', () => this.onEnd());
        document.addEventListener('touchmove', (e) => this.onMove(e), { passive: false });
        document.addEventListener('touchend', () => this.onEnd());
    }
    
    initCropArea() {
        const padding = 20;
        const { displayWidth, displayHeight, type } = this.editor;
        const aspectRatio = this.aspectRatios[type];
        
        let width, height;
        
        if (type === 'avatar') {
            // Square crop for avatar - fit within image
            const maxSize = Math.min(displayWidth, displayHeight) - padding * 2;
            const size = Math.max(this.minSize, maxSize);
            width = height = size;
        } else {
            // Banner with fixed 4:1 aspect ratio
            // Try to fit width first
            width = Math.min(displayWidth - padding * 2, displayWidth * 0.9);
            height = width / aspectRatio;
            
            // If height doesn't fit, scale down
            if (height > displayHeight - padding * 2) {
                height = displayHeight - padding * 2;
                width = height * aspectRatio;
            }
            
            // Ensure minimum sizes
            width = Math.max(this.minSize * 2, width);
            height = Math.max(this.minSize / 2, height);
        }
        
        // Center the crop area
        this.editor.crop = {
            x: Math.max(0, (displayWidth - width) / 2),
            y: Math.max(0, (displayHeight - height) / 2),
            width: width,
            height: height
        };
    }
    
    startDrag(e) {
        if (e.target.classList.contains('crop-handle')) return;
        
        e.preventDefault();
        this.isDragging = true;
        
        const point = e.touches ? e.touches[0] : e;
        this.startX = point.clientX;
        this.startY = point.clientY;
        this.startCrop = { ...this.editor.crop };
    }
    
    startResize(e) {
        e.preventDefault();
        e.stopPropagation();
        
        this.isResizing = true;
        this.resizeHandle = e.target.dataset.handle;
        
        const point = e.touches ? e.touches[0] : e;
        this.startX = point.clientX;
        this.startY = point.clientY;
        this.startCrop = { ...this.editor.crop };
    }
    
    onMove(e) {
        if (!this.isDragging && !this.isResizing) return;
        
        e.preventDefault();
        const point = e.touches ? e.touches[0] : e;
        const dx = point.clientX - this.startX;
        const dy = point.clientY - this.startY;
        
        if (this.isDragging) {
            this.handleDrag(dx, dy);
        } else if (this.isResizing) {
            this.handleResize(dx, dy);
        }
        
        this.editor.updateCropOverlay();
        this.editor.updatePreviews();
    }
    
    handleDrag(dx, dy) {
        const { displayWidth, displayHeight, crop } = this.editor;
        
        let newX = this.startCrop.x + dx;
        let newY = this.startCrop.y + dy;
        
        // Constrain to canvas
        newX = Math.max(0, Math.min(newX, displayWidth - crop.width));
        newY = Math.max(0, Math.min(newY, displayHeight - crop.height));
        
        this.editor.crop.x = newX;
        this.editor.crop.y = newY;
    }
    
    handleResize(dx, dy) {
        const { x, y, width, height } = this.startCrop;
        const { displayWidth, displayHeight, type } = this.editor;
        const handle = this.resizeHandle;
        const aspectRatio = this.aspectRatios[type];
        
        // Calculate size change based on diagonal movement
        // Use the primary axis for each handle
        let sizeDelta = 0;
        
        switch (handle) {
            case 'br': // Bottom-right: positive dx/dy = bigger
                sizeDelta = Math.max(dx, dy * aspectRatio);
                break;
            case 'bl': // Bottom-left: negative dx or positive dy = bigger
                sizeDelta = Math.max(-dx, dy * aspectRatio);
                break;
            case 'tr': // Top-right: positive dx or negative dy = bigger
                sizeDelta = Math.max(dx, -dy * aspectRatio);
                break;
            case 'tl': // Top-left: negative dx/dy = bigger
                sizeDelta = Math.max(-dx, -dy * aspectRatio);
                break;
        }
        
        // Calculate new size
        let newW, newH;
        if (type === 'avatar') {
            newW = newH = width + sizeDelta;
        } else {
            newW = width + sizeDelta;
            newH = newW / aspectRatio;
        }
        
        // Apply minimum size - PREVENT INVERSION
        const minW = type === 'avatar' ? this.minSize : this.minSize * 2;
        const minH = type === 'avatar' ? this.minSize : this.minSize / aspectRatio;
        
        if (newW < minW) {
            newW = minW;
            newH = type === 'avatar' ? minW : minW / aspectRatio;
        }
        if (newH < minH) {
            newH = minH;
            newW = type === 'avatar' ? minH : minH * aspectRatio;
        }
        
        // Calculate new position based on which handle is being dragged
        let newX = x, newY = y;
        
        switch (handle) {
            case 'br':
                // Top-left corner stays fixed
                break;
            case 'bl':
                // Top-right corner stays fixed, so x moves
                newX = x + width - newW;
                break;
            case 'tr':
                // Bottom-left corner stays fixed, so y moves
                newY = y + height - newH;
                break;
            case 'tl':
                // Bottom-right corner stays fixed, so both move
                newX = x + width - newW;
                newY = y + height - newH;
                break;
        }
        
        // Constrain to canvas bounds - adjust size if needed
        
        // Left bound
        if (newX < 0) {
            if (handle === 'bl' || handle === 'tl') {
                // Shrink from left side
                newW = newW + newX; // newX is negative
                newH = type === 'avatar' ? newW : newW / aspectRatio;
                newX = 0;
            }
        }
        
        // Top bound
        if (newY < 0) {
            if (handle === 'tr' || handle === 'tl') {
                // Shrink from top side
                newH = newH + newY; // newY is negative
                newW = type === 'avatar' ? newH : newH * aspectRatio;
                newY = 0;
                // Recalculate X for handles that move X
                if (handle === 'tl') {
                    newX = x + width - newW;
                }
            }
        }
        
        // Right bound
        if (newX + newW > displayWidth) {
            if (handle === 'br' || handle === 'tr') {
                newW = displayWidth - newX;
                newH = type === 'avatar' ? newW : newW / aspectRatio;
            }
        }
        
        // Bottom bound
        if (newY + newH > displayHeight) {
            if (handle === 'br' || handle === 'bl') {
                newH = displayHeight - newY;
                newW = type === 'avatar' ? newH : newH * aspectRatio;
                // Recalculate X for bl handle
                if (handle === 'bl') {
                    newX = x + width - newW;
                }
            }
        }
        
        // Final minimum size check - don't apply if it would violate minimums
        if (newW < minW || newH < minH) {
            return; // Don't update - keep previous valid state
        }
        
        // Ensure X and Y are not negative after all calculations
        newX = Math.max(0, newX);
        newY = Math.max(0, newY);
        
        this.editor.crop = { x: newX, y: newY, width: newW, height: newH };
    }
    
    onEnd() {
        this.isDragging = false;
        this.isResizing = false;
        this.resizeHandle = null;
    }
}
