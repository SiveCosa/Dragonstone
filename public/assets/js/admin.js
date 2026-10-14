// public/assets/js/admin.js
document.addEventListener('DOMContentLoaded', function () {
    // Closeable alerts
    document.querySelectorAll('.alert .close').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.alert').remove());
    });

    // Simple confirmation for delete links/buttons
    document.querySelectorAll('.confirm-delete').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
