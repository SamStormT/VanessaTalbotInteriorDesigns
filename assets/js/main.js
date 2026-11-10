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

    // Contact form: checked in the browser first, then sent to the server,
    // which saves the message and shows the result.
    document.querySelectorAll('[data-contact-form]').forEach((form) => {
        const status = form.querySelector('.form-status');
        const button = form.querySelector('button[type="submit"]');
        const alert = form.querySelector('[data-form-alert]');

        // After sending, the page reloads with a message: bring it into view
        // and give it focus so screen readers announce it. This waits for
        // "load", because the browser's jump to #contact-form happens first.
        if (alert) {
            const showAlert = () => {
                alert.focus({ preventScroll: true });
                alert.scrollIntoView({ block: 'center' });
            };
            if (document.readyState === 'complete') {
                showAlert();
            } else {
                window.addEventListener('load', () => setTimeout(showAlert, 0), { once: true });
            }
        }

        form.addEventListener('submit', (event) => {
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                event.preventDefault();
                status.textContent = 'Please complete the required fields.';
                firstInvalid.focus();
                return;
            }
            // Stop a double click from sending the message twice.
            if (form.dataset.sending) {
                event.preventDefault();
                return;
            }
            form.dataset.sending = '1';
            button.disabled = true;
            status.textContent = 'Sending your message…';
        });

        // Coming back with the browser's back button must not leave it disabled.
        window.addEventListener('pageshow', () => {
            delete form.dataset.sending;
            button.disabled = false;
        });
    });

    // Newsletter form: sent in the background, with the answer shown in
    // place. Without JavaScript it posts normally and the page reloads.
    document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
        const email = form.querySelector('input[type="email"]');
        const status = form.querySelector('.form-status');
        const button = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!email.value.trim() || !email.checkValidity()) {
                email.setAttribute('aria-invalid', 'true');
                status.textContent = 'Please enter a valid email address.';
                email.focus();
                return;
            }

            email.removeAttribute('aria-invalid');
            button.disabled = true;
            status.textContent = 'Signing you up…';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                });
                const result = await response.json();
                status.textContent = result.message;
                if (result.ok) {
                    form.reset();
                } else if (response.status === 422) {
                    email.setAttribute('aria-invalid', 'true');
                    email.focus();
                }
            } catch (error) {
                status.textContent = 'Sorry, we could not sign you up just now. Please try again later.';
            } finally {
                button.disabled = false;
            }
        });

        email.addEventListener('input', () => {
            if (email.getAttribute('aria-invalid') === 'true' && email.checkValidity()) {
                email.removeAttribute('aria-invalid');
                status.textContent = '';
            }
        });
    });
});
