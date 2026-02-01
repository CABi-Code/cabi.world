<script>
// Profile page specific functionality
// Image upload is handled by the global image-editor module

//const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

// Toggle application visibility
async function toggleHidden(id) {
    await fetch('/api/application/toggle-hidden', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}

// Delete application
async function deleteApp(id) {
    if (!confirm('Удалить заявку?')) return;
    await fetch('/api/application/delete', {
        method: 'POST', 
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ id })
    });
    location.reload();
}
</script>
