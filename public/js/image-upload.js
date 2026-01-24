// image-upload.js
export function initImageUpload(csrf) {
    let currentUploadType = null;
    let currentFile = null;
    let cropData = { x: 0, y: 0, scale: 1 };
    
    const imgEditorModal = document.getElementById('imgEditorModal');
    const editorPreview = document.getElementById('editorPreview');
    const zoomRange = document.getElementById('zoomRange');
    
    const setupUpload = (triggerId, inputId, type) => {
        const trigger = document.getElementById(triggerId);
        const input = document.getElementById(inputId);
        if (!trigger || !input) return;
        
        trigger.addEventListener('click', () => input.click());
        
        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            
            currentFile = file;
            currentUploadType = type;
            cropData = { x: 0, y: 0, scale: 1 };
            
            const reader = new FileReader();
            reader.onload = ev => {
                if (editorPreview && imgEditorModal) {
                    document.getElementById('editorTitle').textContent = 
                        type === 'avatar' ? 'Редактировать аватар' : 'Редактировать баннер';
                    
                    editorPreview.innerHTML = `<img src="${ev.target.result}" id="cropImg" style="transform-origin:center;cursor:move;">`;
                    if (zoomRange) zoomRange.value = 1;
                    imgEditorModal.style.display = 'flex';
                    
                    setupCrop();
                }
            };
            reader.readAsDataURL(file);
            input.value = '';
        });
    };
    
    const setupCrop = () => {
        const img = document.getElementById('cropImg');
        if (!img) return;
        
        let isDragging = false;
        let startX, startY;
        
        img.addEventListener('mousedown', e => {
            isDragging = true;
            startX = e.clientX - cropData.x;
            startY = e.clientY - cropData.y;
        });
        
        document.addEventListener('mousemove', e => {
            if (!isDragging) return;
            cropData.x = e.clientX - startX;
            cropData.y = e.clientY - startY;
            updateCropTransform();
        });
        
        document.addEventListener('mouseup', () => isDragging = false);
        
        if (zoomRange) {
            zoomRange.addEventListener('input', () => {
                cropData.scale = parseFloat(zoomRange.value);
                updateCropTransform();
            });
        }
    };
    
    const updateCropTransform = () => {
        const img = document.getElementById('cropImg');
        if (img) img.style.transform = `translate(${cropData.x}px, ${cropData.y}px) scale(${cropData.scale})`;
    };
    
    document.getElementById('saveImgBtn')?.addEventListener('click', async () => {
        if (!currentFile) return;
        
        const btn = document.getElementById('saveImgBtn');
        btn.disabled = true;
        btn.textContent = 'Загрузка...';
        
        const formData = new FormData();
        formData.append(currentUploadType, currentFile);
        formData.append('crop_x', cropData.x);
        formData.append('crop_y', cropData.y);
        formData.append('crop_scale', cropData.scale);
        
        try {
            const endpoint = currentUploadType === 'avatar' ? '/api/user/avatar' : '/api/user/banner';
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf },
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка загрузки');
                btn.disabled = false;
                btn.textContent = 'Сохранить';
            }
        } catch (err) {
            alert('Ошибка сети');
            btn.disabled = false;
            btn.textContent = 'Сохранить';
        }
    });
    
    setupUpload('avatarUpload', 'avatarInput', 'avatar');
    setupUpload('bannerUpload', 'bannerInput', 'banner');

    // === Delete Avatar/Banner ===
    document.getElementById('deleteAvatar')?.addEventListener('click', async () => {
        if (!confirm('Удалить аватар?')) return;
        try {
            const res = await fetch('/api/user/avatar/delete', { method: 'POST', headers: { 'X-CSRF-Token': csrf } });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Ошибка');
        } catch (err) { alert('Ошибка сети'); }
    });

    document.getElementById('deleteBanner')?.addEventListener('click', async () => {
        if (!confirm('Удалить баннер?')) return;
        try {
            const res = await fetch('/api/user/banner/delete', { method: 'POST', headers: { 'X-CSRF-Token': csrf } });
            const data = await res.json();
            if (data.success) location.reload();
            else alert(data.error || 'Ошибка');
        } catch (err) { alert('Ошибка сети'); }
    });
}