// ========== Drag & Drop ==========

// Типы сущностей, в которые можно класть элементы
const entityTypes = ['category', 'modpack', 'mod'];

document.addEventListener('DOMContentLoaded', () => {
    if (!isOwner) return;
    initDragDrop();
});

function initDragDrop() {
    const items = document.querySelectorAll('.folder-item[draggable="true"]');
    
    items.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('dragleave', handleDragLeave);
        item.addEventListener('drop', handleDrop);
    });
    
    // Drop zones
    document.querySelectorAll('.folder-drop-zone').forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleDrop);
    });
    
    // Меняем курсор
    items.forEach(item => {
        const row = item.querySelector('.folder-item-row');
        if (row) {
            row.addEventListener('mouseenter', (e) => {
                // Не меняем курсор на кнопках и ссылках
                if (e.target.closest('button, a, .folder-item-actions')) return;
                row.style.cursor = 'grab';
            });
            row.addEventListener('mouseleave', () => {
                row.style.cursor = '';
            });
        }
    });
}

function handleDragStart(e) {
    draggedItem = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.id);
    
    // Скрываем недопустимые цели
    setTimeout(() => highlightDropTargets(this), 0);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedItem = null;
    clearDropHighlights();
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    const target = getDropTarget(e.target);
    if (target && canDrop(draggedItem, target)) {
        target.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    const target = getDropTarget(e.target);
    if (target) {
        target.classList.remove('drag-over');
    }
}

function handleDrop(e) {
    e.preventDefault();
    
    const target = getDropTarget(e.target);
    if (!target || !draggedItem) return;
    
    target.classList.remove('drag-over');
    
    if (!canDrop(draggedItem, target)) return;
    
    const itemId = parseInt(draggedItem.dataset.id);
    let newParentId = null;
    let afterId = null;
    
    if (target.classList.contains('folder-drop-zone')) {
        newParentId = target.dataset.parent === 'null' ? null : parseInt(target.dataset.parent);
    } else if (target.classList.contains('folder-item')) {
        const targetType = target.dataset.type;
        
        if (entityTypes.includes(targetType)) {
            // Перетаскиваем внутрь сущности
            newParentId = parseInt(target.dataset.id);
        } else {
            // Перетаскиваем рядом с элементом
            newParentId = target.dataset.parent === 'null' ? null : parseInt(target.dataset.parent);
            afterId = parseInt(target.dataset.id);
        }
    }
    
    moveItem(itemId, newParentId, afterId);
}

function getDropTarget(el) {
    return el.closest('.folder-item, .folder-drop-zone');
}

function canDrop(dragged, target) {
    if (!dragged || !target) return false;
    if (dragged === target) return false;
    
    // Нельзя перетащить в самого себя или в своих потомков
    if (target.closest(`[data-id="${dragged.dataset.id}"]`)) return false;
    
    // Проверяем, является ли цель сущностью
    if (target.classList.contains('folder-item')) {
        const targetType = target.dataset.type;
        // В элементы (не сущности) нельзя класть другие элементы
        if (!entityTypes.includes(targetType) && !entityTypes.includes(dragged.dataset.type)) {
            // Оба - элементы, можно положить рядом
            return true;
        }
    }
    
    return true;
}

function highlightDropTargets(dragged) {
    document.querySelectorAll('.folder-item').forEach(item => {
        if (canDrop(dragged, item)) {
            item.classList.add('drop-target');
        } else {
            item.classList.add('drop-disabled');
        }
    });
}

function clearDropHighlights() {
    document.querySelectorAll('.drag-over, .drop-target, .drop-disabled').forEach(el => {
        el.classList.remove('drag-over', 'drop-target', 'drop-disabled');
    });
}

async function moveItem(itemId, newParentId, afterId) {
    try {
        const res = await fetch('/api/user-folder/move', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({
                item_id: itemId,
                parent_id: newParentId,
                after_id: afterId
            })
        });
        
        if (res.ok) {
            location.reload();
        } else {
            const data = await res.json();
            alert(data.error || 'Ошибка перемещения');
        }
    } catch (err) {
        console.error('Move error:', err);
    }
}
