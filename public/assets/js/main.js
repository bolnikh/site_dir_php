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

    // ================================================
    // Валидация формы добавления сайта
    // ================================================
    const addForm = document.getElementById('add-site-form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            let valid = true;

            // Очистка предыдущих ошибок
            addForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            addForm.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            // 1. Раздел сайта
            const sectionId = document.getElementById('section_id');
            if (!sectionId.value) {
                showError(sectionId, 'Выберите раздел.');
                valid = false;
            }

            // 2. Название сайта
            const name = document.getElementById('name');
            if (!name.value.trim()) {
                showError(name, 'Введите название сайта.');
                valid = false;
            } else if (name.value.trim().length > 512) {
                showError(name, 'Название не должно превышать 512 символов.');
                valid = false;
            }

            // 3. URL сайта
            const url = document.getElementById('url');
            if (!url.value.trim()) {
                showError(url, 'Введите URL сайта.');
                valid = false;
            } else if (!isValidUrl(url.value.trim())) {
                showError(url, 'Введите корректный URL (например, https://example.com).');
                valid = false;
            }

            // 4. Описание
            const description = document.getElementById('description');
            if (description) {
                // Summernote — проверяем и текст, и HTML
                const descText = description.value.trim() || description.textContent.trim();
                if (!descText || descText === '<p><br></p>') {
                    showError(description, 'Введите описание сайта.');
                    valid = false;
                }
            }

            // 5. Email (опционально)
            const email = document.getElementById('email');
            if (email.value.trim() && !isValidEmail(email.value.trim())) {
                showError(email, 'Введите корректный email.');
                valid = false;
            }

            // 6. Согласие с правилами
            const agreement = document.getElementById('agreement');
            if (!agreement.checked) {
                showError(agreement, 'Необходимо принять условия и согласиться с правилами.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                // Скролл к первой ошибке
                const firstError = addForm.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    // ================================================
    // Валидация формы обратной связи
    // ================================================
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            let valid = true;

            contactForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            contactForm.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const email = document.getElementById('email');
            if (!email.value.trim()) {
                showError(email, 'Введите email.');
                valid = false;
            } else if (!isValidEmail(email.value.trim())) {
                showError(email, 'Введите корректный email.');
                valid = false;
            }

            const message = document.getElementById('message');
            if (!message.value.trim()) {
                showError(message, 'Введите сообщение.');
                valid = false;
            }

            const agreement = document.getElementById('agreement');
            if (!agreement.checked) {
                showError(agreement, 'Необходимо дать согласие на обработку данных.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                const firstError = contactForm.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});

/**
 * Показать ошибку валидации у поля
 */
function showError(element, message) {
    element.classList.add('is-invalid');
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    element.parentNode.appendChild(feedback);
}

/**
 * Проверка URL
 */
function isValidUrl(url) {
    try {
        const u = new URL(url);
        return u.protocol === 'http:' || u.protocol === 'https:';
    } catch {
        return false;
    }
}

/**
 * Проверка Email
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
