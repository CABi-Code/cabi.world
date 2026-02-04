<script>

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        // TODO: показать уведомление
    });
};

</script>