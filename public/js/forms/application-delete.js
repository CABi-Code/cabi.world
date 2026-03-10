// js/forms/application-delete.js

export function initApplicationDelete(csrf) {
    window.deleteApp = async function (id) {
        if (!id) {
            alert('Ошибка: ID заявки не передан');
            return;
        }

        if (!confirm(`Вы уверены, что хотите удалить заявку №${id}?`)) {
            return;
        }

        try {
            const response = await fetch(`/api/application/delete/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': csrf || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

			if (response.ok && result.success) {
				const appCard = document.querySelector(`.app-card[data-app-id="${id}"]`);
				if (appCard) {
					createShatterEffect(appCard);
				} else {
					location.reload();
				}
			} else {
                const msg = result.message || result.error || 'Не удалось удалить заявку';
                alert(`Ошибка: ${msg}`);
            }
        } catch (err) {
            console.error('Delete error:', err);
            alert('Произошла ошибка при соединении с сервером');
        }
    };
}

// анимация 
function createShatterEffect(element) {
    if (!element) return;

    const rect = element.getBoundingClientRect();
    const container = document.createElement('div');
    
    container.style.position = 'absolute';
    container.style.left = `${rect.left + window.scrollX}px`;
    container.style.top = `${rect.top + window.scrollY}px`;
    container.style.width = `${rect.width}px`;
    container.style.height = `${rect.height}px`;
    container.style.pointerEvents = 'none';
    container.style.zIndex = '1000';
    container.style.overflow = 'hidden';
    container.style.transformOrigin = 'center';   // важно для красивого сжатия

    document.body.appendChild(container);

    // Скрываем оригинальную карточку сразу
    element.style.transition = 'opacity 0.1s';
    element.style.opacity = '0';

    const piecesCount = 14;
    const colors = ['#f3f4f6', '#e5e7eb', '#d1d5db', '#9ca3af'];

    for (let i = 0; i < piecesCount; i++) {
        const piece = document.createElement('div');
        
        const size = 20 + Math.random() * 45;
        const x = Math.random() * (rect.width - size);
        const y = Math.random() * (rect.height - size);
        
        piece.style.position = 'absolute';
        piece.style.left = `${x}px`;
        piece.style.top = `${y}px`;
        piece.style.width = `${size}px`;
        piece.style.height = `${size}px`;
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.borderRadius = `${Math.random() * 8 + 2}px`;
        piece.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
        piece.style.opacity = '0.95';
        piece.style.transformOrigin = 'center';
        
        const rotation = (Math.random() - 0.5) * 120;
        piece.style.transform = `rotate(${rotation}deg)`;

        container.appendChild(piece);

        const angle = Math.random() * Math.PI * 2;
        const velocity = 80 + Math.random() * 140;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity - 40;
        
        const rotSpeed = (Math.random() - 0.5) * 800;
        const scaleEnd = 0.3 + Math.random() * 0.4;

        setTimeout(() => {
            piece.style.transition = `transform 0.65s cubic-bezier(0.4, 0.0, 0.2, 1), opacity 0.65s ease-out`;
            piece.style.transform = `translate(${vx}px, ${vy}px) rotate(${rotation + rotSpeed}deg) scale(${scaleEnd})`;
            piece.style.opacity = '0';
        }, 30 + i * 25);
    }
	
	
	const tn = 920;
	const tk = 900;
	
    // ← Новая часть: плавное сжатие контейнера
	setTimeout(() => {
		// Сначала устанавливаем transition
		container.style.transition = 'transform 0.85s cubic-bezier(0.4, 0.0, 0.2, 1), opacity 0.85s ease-out';
		container.style.transformOrigin = 'center center';

		// Force reflow — обязательно!
		void container.offsetWidth;

		// Теперь применяем изменения — анимация должна сработать
		container.style.transform = 'scale(0.08)';
		container.style.opacity = '0';
	}, tn); // начинаем сжатие после того, как частички разлетелись

    // Удаляем контейнер после завершения анимации сжатия
	setTimeout(() => {
		container.remove();
		
		const feedCard = element.closest('.feed-card');
		element.remove();
		
		if (feedCard && feedCard.children.length === 0) {
			feedCard.remove();
		}
		
		if (document.querySelectorAll('.app-card').length === 0) {
			showEmptyState();
		}
	}, tn + tk); // 850 (начало сжатия) + 650 (длительность анимации)
}

function showEmptyState() {
    const appList = document.querySelector('.app-list');
    if (!appList || appList.querySelector('.empty-state')) return;

    const emptyDiv = document.createElement('div');
    emptyDiv.className = 'empty-state';
    emptyDiv.style.cssText = 'text-align:center; padding:3rem 1rem; color:var(--text-muted);';
    emptyDiv.innerHTML = `
        <div style="font-size:3rem; margin-bottom:1rem; opacity:0.4;">📭</div>
        <p style="margin:0.5rem 0; font-size:1.1rem;">Заявок нет</p>
    `;
    appList.appendChild(emptyDiv);
}