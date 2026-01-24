<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

async function deleteMyApp(id) {
	if (!confirm('Удалить заявку?')) return;
	await fetch('/api/application/delete', {
		method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
		body: JSON.stringify({ id })
	});
	location.reload();
}

document.querySelectorAll('[data-modal]').forEach(btn => {
	btn.addEventListener('click', () => {
		document.getElementById(btn.dataset.modal).style.display = 'flex';
	});
});
</script>
