/**
 * Vanessa Talbot Interior Designs — admin scripts (placeholder)
 */
document.addEventListener('DOMContentLoaded', () => {
    // Mobile sidebar toggle
    const toggle = document.querySelector('.admin-topbar__menu');
    const sidebar = document.getElementById('admin-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    }
});

// Ask before destructive actions.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
