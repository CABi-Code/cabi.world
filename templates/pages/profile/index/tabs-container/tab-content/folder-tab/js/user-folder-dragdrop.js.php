<script>
let draggedElement = null;
let isOwner = window.isOwner;   // получаем из main.js

function initDragAndDrop() {
    if (!isOwner) return;

    const structure = document.querySelector('.community-structure');
    if (!structure) return;

    structure.addEventListener('dragstart', handleDragStart, true);
    structure.addEventListener('dragend', handleDragEnd, true);
    structure.addEventListener('dragover', handleDragOver);
    structure.addEventListener('dragleave', handleDragLeave);
    structure.addEventListener('drop', handleDrop);

    structure.addEventListener('dragover', handleRootDragOver);
    structure.addEventListener('drop', handleRootDrop);
}

document.addEventListener('DOMContentLoaded', initDragAndDrop);

function getDropPosition(e, element) {
	const rect = element.getBoundingClientRect();
	const y = e.clientY - rect.top;
	const h = rect.height;
	const isEntity = element.dataset.isEntity === '1';

	// Увеличили центральную зону для папок (25%–75%) — теперь гораздо легче попасть
	if (isEntity && y > h * 0.13 && y < h * 0.87) {
		return 'inside';
	}
	return y < h * 0.5 ? 'before' : 'after';
}

function findPreviousSibling(element) {
	let sibling = element.previousElementSibling;
	while (sibling && !sibling.classList.contains('folder-item')) {
		sibling = sibling.previousElementSibling;
	}
	return sibling;
}

function handleDragStart(e) {
	const item = e.target.closest('.folder-item[draggable="true"]');
	if (!item) return;

	draggedElement = item;
	item.classList.add('dragging');

	// Отключаем потомков
	document.querySelectorAll('.folder-item').forEach(el => {
		if (isDescendantOf(el, item)) {
			el.classList.add('drag-disabled');
			el.draggable = false;
		}
	});

	e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
	if (draggedElement) {
		draggedElement.classList.remove('dragging');
	}

	document.querySelectorAll('.folder-item').forEach(item => {
		item.classList.remove('dragging', 'drag-over', 'drop-before', 'drop-inside', 'drop-after', 'drag-disabled');
		item.draggable = true;
	});

	draggedElement = null;
}

function handleDragOver(e) {
	const item = e.target.closest('.folder-item');
	if (!item || !draggedElement || draggedElement === item || item.classList.contains('drag-disabled')) {
		return;
	}

	e.preventDefault();

	const position = getDropPosition(e, item);

	item.classList.remove('drop-before', 'drop-inside', 'drop-after', 'drag-over');
	item.classList.add(`drop-${position}`, 'drag-over');
}

function handleDragLeave(e) {
	const item = e.target.closest('.folder-item');
	if (item) {
		item.classList.remove('drag-over', 'drop-before', 'drop-inside', 'drop-after');
	}
}

async function handleDrop(e) {
	const item = e.target.closest('.folder-item');
	if (!item || draggedElement === item) return;

	e.preventDefault();
	e.stopPropagation();

	const position = getDropPosition(e, item);
	const draggedId = parseInt(draggedElement.dataset.id);
	const targetId = parseInt(item.dataset.id);
	const targetIsEntity = item.dataset.isEntity === '1';
	const targetOrder = parseFloat(item.dataset.sortOrder || 0);

	let newParentId = null;
	let newSortOrder = 0;

	if (position === 'inside' && targetIsEntity) {
		newParentId = targetId;
		// Берём максимум в папке + 1 (нужно собрать все data-sort-order детей)
		const childrenOrders = Array.from(item.querySelectorAll('.folder-children .folder-item'))
			.map(el => parseFloat(el.dataset.sortOrder || 0));
		newSortOrder = childrenOrders.length ? Math.max(...childrenOrders) + 1 : 1;
	} else {
		newParentId = item.dataset.parent === 'root' ? null : parseInt(item.dataset.parent);
		
		if (position === 'before') {
			const prev = findPreviousSibling(item);
			const prevOrder = prev ? parseFloat(prev.dataset.sortOrder || 0) : 0;
			newSortOrder = (prevOrder + targetOrder) / 2;   // ← тут проблема с int
		} else {
			newSortOrder = targetOrder + 1;
		}
	}

	// Отправляем
	const body = {
		item_id: draggedId,
		parent_id: newParentId,
		new_sort_order: newSortOrder
	};

	const res = await fetch('/api/user-folder/move', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
		body: JSON.stringify(body)
	});

	if (res.ok) location.reload();
}

function handleRootDragOver(e) {
	if (!draggedElement || e.target.closest('.folder-item')) return;
	e.preventDefault();
	// добавить класс на .community-structure для визуальной подсветки корня
}

async function handleRootDrop(e) {
	if (!draggedElement || e.target.closest('.folder-item')) return;
	e.preventDefault();

	try {
		const res = await fetch('/api/user-folder/move', {
			method: 'POST',
			headers: { 
				'Content-Type': 'application/json', 
				'X-CSRF-Token': csrf 
			},
			body: JSON.stringify({ 
				item_id: draggedElement.dataset.id, 
				parent_id: null, 
				after_id: null // в конец корневого уровня
			})
		});

		if (res.ok) {
			location.reload();
		} else {
			const data = await res.json();
			alert(data.error || 'Ошибка перемещения в корень');
		}
	} catch (err) {
		alert('Ошибка сети');
	}
}

function isDescendantOf(child, parent) {
	let node = child.parentElement;
	while (node) {
		if (node === parent) return true;
		node = node.parentElement;
	}
	return false;
}

</script>