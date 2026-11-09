/**
 * Vanessa Talbot Interior Designs — public site scripts
 */
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('primary-nav');

    // Expose the sticky header height so other sticky elements and anchor
    // targets can sit below it.
    const setHeaderHeight = () => {
        if (header) {
            document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
        }
    };
    setHeaderHeight();
    window.addEventListener('resize', setHeaderHeight);

    // Mobile navigation toggle
    if (toggle && nav) {
        const closeNav = () => {
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            const isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        // Close with Escape and return focus to the menu button
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && nav.classList.contains('is-open')) {
                closeNav();
                toggle.focus();
            }
        });

        // Reset when resizing back to the desktop layout
        window.matchMedia('(min-width: 1101px)').addEventListener('change', (mq) => {
            if (mq.matches) closeNav();
        });
    }

    // Contact form: not connected yet, so it validates and says so plainly.
    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        const status = form.querySelector('.form-status');

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            status.textContent = form.checkValidity()
                ? status.dataset.notice
                : 'Please complete the required fields.';
        });
    });

    // Newsletter form.
    // Sign-ups are not stored until the subscriber database is built (Phase 3),
    // so the form validates the email and tells the visitor honestly.
    document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
        const email = form.querySelector('input[type="email"]');
        const status = form.querySelector('.form-status');

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!email.value.trim() || !email.checkValidity()) {
                email.setAttribute('aria-invalid', 'true');
                status.textContent = 'Please enter a valid email address.';
                email.focus();
                return;
            }

            email.removeAttribute('aria-invalid');
            status.textContent = 'Thank you for your interest! Newsletter sign-ups open very soon. '
                + 'In the meantime, you are welcome to get in touch through the contact page.';
        });

        email.addEventListener('input', () => {
            if (email.getAttribute('aria-invalid') === 'true' && email.checkValidity()) {
                email.removeAttribute('aria-invalid');
                status.textContent = '';
            }
        });
    });
});
