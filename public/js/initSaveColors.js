// (Дополнительный файл для save colors, поскольку он не в image-upload, но структура не имеет отдельного - добавляем как initSaveColors в main)
export function initSaveColors(csrf) {
    document.getElementById('saveColors')?.addEventListener('click', async () => {
        const btn = document.getElementById('saveColors');
        btn.disabled = true;
        btn.textContent = '...';
        
        const data = {
            banner_bg_value: document.querySelector('[name="banner_color1"]')?.value + ',' + 
                            document.querySelector('[name="banner_color2"]')?.value,
            avatar_bg_value: document.querySelector('[name="avatar_color1"]')?.value + ',' + 
                            document.querySelector('[name="avatar_color2"]')?.value
        };
        
        try {
            await fetch('/api/user/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify(data)
            });
            btn.textContent = 'Сохранено!';
            setTimeout(() => { btn.disabled = false; btn.textContent = 'Сохранить цвета'; }, 2000);
        } catch (err) {
            btn.disabled = false;
            btn.textContent = 'Ошибка';
        }
    });
}