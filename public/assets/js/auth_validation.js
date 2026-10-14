// =======================================================
// Login & Register Form Validation
// =======================================================
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.auth-form');

    forms.forEach(form => {
        form.addEventListener('submit', e => {
            let valid = true;
            const email = form.querySelector('input[name="email"]');
            const password = form.querySelector('input[name="password"]');
            const errors = form.querySelector('.error-msg');
            errors.textContent = '';

            if (!email.value.match(/^[^@\s]+@[^@\s]+\.[^@\s]+$/)) {
                errors.textContent = 'Please enter a valid email.';
                valid = false;
            } else if (password.value.length < 6) {
                errors.textContent = 'Password must be at least 6 characters.';
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    });
});
