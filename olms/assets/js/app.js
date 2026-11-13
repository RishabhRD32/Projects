'use strict';

// Small progressive-enhancement helpers; the application remains usable without JavaScript.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        const close = alert.querySelector('.alert-close');
        if (close) close.addEventListener('click', () => alert.remove());
    });

    const policyForm = document.getElementById('policy-form');
    if (policyForm) {
        const id = document.getElementById('policy-id');
        const name = document.getElementById('policy-name');
        const code = document.getElementById('policy-code');
        const quota = document.getElementById('policy-quota');
        const description = document.getElementById('policy-description');
        const title = document.getElementById('policy-form-title');

        document.querySelectorAll('[data-edit-policy]').forEach((button) => {
            button.addEventListener('click', () => {
                id.value = button.dataset.id || '';
                name.value = button.dataset.name || '';
                code.value = button.dataset.code || '';
                quota.value = button.dataset.quota || '0';
                description.value = button.dataset.description || '';
                title.textContent = 'Edit leave type';
                policyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                name.focus();
            });
        });

        const reset = document.getElementById('policy-reset');
        if (reset) reset.addEventListener('click', () => {
            window.setTimeout(() => { id.value = ''; title.textContent = 'Add leave type'; }, 0);
        });
    }

    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    if (startDate && endDate) {
        startDate.addEventListener('change', () => {
            if (startDate.value) endDate.min = startDate.value;
            if (endDate.value && startDate.value && endDate.value < startDate.value) endDate.value = startDate.value;
        });
    }

    document.querySelectorAll('form[onsubmit]').forEach((form) => {
        // Keep inline confirm handlers functional; this hook is intentionally not overriding them.
        form.addEventListener('submit', (event) => {
            if (form.dataset.preventDoubleSubmit === 'true') {
                if (form.dataset.submitting === 'true') event.preventDefault();
                else form.dataset.submitting = 'true';
            }
        });
    });
});
