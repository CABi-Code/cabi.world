<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

let currentUploadType = null;
let currentFile = null;
let cropData = { x: 0, y: 0, scale: 1 };

document.getElementById('avatarEditBtn')?.addEventListener('click', () => document.getElementById('avatarInput').click());
document.getElementById('bannerEditBtn')?.addEventListener('click', () => document.getElementById('bannerInput').click());

['avatar', 'banner'].forEach(type => {
	document.getElementById(type + 'Input')?.addEventListener('change', function(e) {
		const file = e.target.files[0];
		if (!file || !file.type.startsWith('image/')) return;
		
		currentFile = file;
		currentUploadType = type;
		cropData = { x: 0, y: 0, scale: 1 };
		
		const reader = new FileReader();
		reader.onload = ev => {
			document.getElementById('editorTitle').textContent = type === 'avatar' ? 'Редактировать аватар' : 'Редактировать баннер';
			document.getElementById('editorPreview').innerHTML = `<img src="${ev.target.result}" id="cropImg" style="transform-origin:center;cursor:move;">`;
			document.getElementById('zoomRange').value = 1;
			document.getElementById('imgEditorModal').style.display = 'flex';
			setupCrop();
		};
		reader.readAsDataURL(file);
		this.value = '';
	});
});

function setupCrop() {
	const img = document.getElementById('cropImg');
	if (!img) return;
	let isDragging = false, startX, startY;
	
	img.onmousedown = e => { isDragging = true; startX = e.clientX - cropData.x; startY = e.clientY - cropData.y; };
	document.onmousemove = e => { if (isDragging) { cropData.x = e.clientX - startX; cropData.y = e.clientY - startY; updateCrop(); } };
	document.onmouseup = () => isDragging = false;
	
	document.getElementById('zoomRange').oninput = function() { cropData.scale = parseFloat(this.value); updateCrop(); };
}

function updateCrop() {
	const img = document.getElementById('cropImg');
	if (img) img.style.transform = `translate(${cropData.x}px, ${cropData.y}px) scale(${cropData.scale})`;
}

document.getElementById('saveImgBtn')?.addEventListener('click', async () => {
	if (!currentFile) return;
	const btn = document.getElementById('saveImgBtn');
	btn.disabled = true; btn.textContent = '...';
	
	const fd = new FormData();
	fd.append(currentUploadType, currentFile);
	
	const res = await fetch('/api/user/' + currentUploadType, { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd });
	const data = await res.json();
	if (data.success) location.reload();
	else { alert(data.error || 'Ошибка'); btn.disabled = false; btn.textContent = 'Сохранить'; }
});

async function toggleHidden(id) {
	await fetch('/api/application/toggle-hidden', {
		method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
		body: JSON.stringify({ id })
	});
	location.reload();
}

async function deleteApp(id) {
	if (!confirm('Удалить заявку?')) return;
	await fetch('/api/application/delete', {
		method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
		body: JSON.stringify({ id })
	});
	location.reload();
}

document.querySelectorAll('#imgEditorModal [data-close]').forEach(el => {
	el.addEventListener('click', () => document.getElementById('imgEditorModal').style.display = 'none');
});
</script>