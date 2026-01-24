// image-editor/index.js - Main entry point

import { ImageEditor } from './ImageEditor.js';

// Global editor instance
let editorInstance = null;

/**
 * Get or create the global image editor instance
 */
export function getImageEditor(csrf) {
    if (!editorInstance) {
        editorInstance = new ImageEditor({ csrf });
    }
    return editorInstance;
}

/**
 * Initialize image upload triggers on the page
 * Works for both settings page and profile page
 */
export function initAdvancedImageUpload(csrf) {
    const editor = getImageEditor(csrf);
    
    // Setup function that handles both click and file input
    const setupUploadTrigger = (triggers, inputId, type) => {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        // Handle file selection - open editor
        const handleFileSelect = (e) => {
            const file = e.target.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;
            
            editor.open(file, type);
            input.value = ''; // Reset input
        };
        
        // Remove any existing listeners by replacing input
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
        newInput.addEventListener('change', handleFileSelect);
        
        // Setup click triggers
        const triggerIds = Array.isArray(triggers) ? triggers : [triggers];
        triggerIds.forEach(triggerId => {
            const trigger = document.getElementById(triggerId);
            if (!trigger) return;
            
            // Remove existing listeners by cloning
            const newTrigger = trigger.cloneNode(true);
            trigger.parentNode.replaceChild(newTrigger, trigger);
            
            newTrigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                document.getElementById(inputId).click();
            });
        });
    };
    
    // Avatar uploads - settings page and profile page
    setupUploadTrigger(['avatarUpload', 'avatarEditBtn'], 'avatarInput', 'avatar');
    
    // Banner uploads - settings page and profile page  
    setupUploadTrigger(['bannerUpload', 'bannerEditBtn'], 'bannerInput', 'banner');
    
    // Delete handlers
    setupDeleteHandler('deleteAvatar', '/api/user/avatar/delete', csrf);
    setupDeleteHandler('deleteBanner', '/api/user/banner/delete', csrf);
    
    return editor;
}

/**
 * Setup delete button handler
 */
function setupDeleteHandler(buttonId, endpoint, csrf) {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    
    // Remove existing listeners by cloning
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);
    
    newBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const itemName = buttonId.includes('Avatar') ? 'аватар' : 'баннер';
        if (!confirm(`Удалить ${itemName}?`)) return;
        
        try {
            const res = await fetch(endpoint, { 
                method: 'POST', 
                headers: { 'X-CSRF-Token': csrf } 
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка');
            }
        } catch (err) { 
            alert('Ошибка сети'); 
        }
    });
}

// Re-export ImageEditor class
export { ImageEditor };
