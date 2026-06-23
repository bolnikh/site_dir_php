/**
 * Каталог сайтов — общий JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Автоматическое скрытие flash-сообщений через 5 секунд
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Подтверждение опасных действий
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
