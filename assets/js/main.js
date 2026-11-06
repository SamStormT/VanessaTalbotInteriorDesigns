/**
 * Vanessa Talbot Interior Designs — public site scripts (placeholder)
 */
document.addEventListener('DOMContentLoaded', () => {
    // Mobile navigation toggle
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('primary-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    }
});
