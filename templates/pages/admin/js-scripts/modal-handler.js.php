// Обработчик клика по строке таблицы
document.querySelectorAll('.admin-table tbody tr[data-app-id]').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', (e) => {
        // Не открывать если кликнули по кнопке или ссылке
        if (e.target.closest('button, a, .admin-actions')) return;
        
        const appId = row.dataset.appId;
        viewAppDetails(appId);
    });
});

async function viewAppDetails(id) {
    try {
        // Загружаем модалку с сервера
        await loadModal('/api/admin/application/' + id + '/modal', {
            id: 'appDetailsModal',
            data: { id }
        });
    } catch (err) {
        console.error('Failed to load modal:', err);
        alert('Ошибка загрузки');
    }
}
