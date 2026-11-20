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

    // Staff only: show or hide the Edit buttons; the choice is remembered.
    const editToggle = document.querySelector('[data-edit-toggle]');
    if (editToggle) {
        const apply = (hidden) => {
            document.body.classList.toggle('edit-hidden', hidden);
            editToggle.textContent = hidden ? 'Show edit buttons' : 'Hide edit buttons';
            editToggle.setAttribute('aria-pressed', String(!hidden));
        };
        let hidden = false;
        try { hidden = localStorage.getItem('vt-edit-hidden') === '1'; } catch (error) { /* storage blocked */ }
        apply(hidden);
        editToggle.addEventListener('click', () => {
            hidden = !hidden;
            apply(hidden);
            try { localStorage.setItem('vt-edit-hidden', hidden ? '1' : '0'); } catch (error) { /* storage blocked */ }
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

// ---------------------------------------------------------------------------
// Staff only (body.is-staff): hovering anything editable outlines it and
// shows "Edit: <field>", which opens that exact field in the admin area.
// Before leaving for the admin area, remember where we were, so "Save and
// return to the page" can bring us back to the same spot.
// ---------------------------------------------------------------------------
(() => {
    const body = document.body;
    if (!body.classList.contains('is-staff')) return;

    const remember = () => {
        try {
            sessionStorage.setItem('vt-return', JSON.stringify({
                url: location.pathname + location.search,
                scroll: Math.round(window.scrollY),
                title: document.title.split(' | ')[0],
                time: Date.now(),
            }));
        } catch (error) { /* storage blocked: the return button just won't appear */ }
    };

    // Coming back after saving: scroll to where we were and say so.
    try {
        const restore = JSON.parse(sessionStorage.getItem('vt-restore') || 'null');
        if (restore && restore.url === location.pathname + location.search) {
            sessionStorage.removeItem('vt-restore');
            window.addEventListener('load', () => setTimeout(() => window.scrollTo(0, restore.scroll), 0), { once: true });
            if (restore.saved) {
                const toast = document.createElement('p');
                toast.className = 'edit-toast';
                toast.setAttribute('role', 'status');
                toast.textContent = 'Saved. This is how it looks now.';
                body.append(toast);
                setTimeout(() => toast.remove(), 4000);
            }
        }
    } catch (error) { /* ignore */ }

    const chip = document.createElement('a');
    chip.className = 'edit-chip';
    chip.hidden = true;
    body.append(chip);

    let current = null;
    let hideTimer = 0;

    const place = (element) => {
        const box = element.getBoundingClientRect();
        const above = box.top > 34;
        chip.style.top = `${window.scrollY + (above ? box.top - 30 : box.top + 4)}px`;
        chip.style.left = `${Math.max(8, Math.min(window.scrollX + box.left, window.scrollX + window.innerWidth - chip.offsetWidth - 8))}px`;
    };
    const hide = () => {
        chip.hidden = true;
        current?.classList.remove('is-edit-hover');
        current = null;
    };

    document.addEventListener('mouseover', (event) => {
        if (body.classList.contains('edit-hidden')) return;
        if (event.target.closest('.edit-chip')) {
            clearTimeout(hideTimer);
            return;
        }
        const element = event.target.closest('[data-edit]');
        if (!element) {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(hide, 250);
            return;
        }
        clearTimeout(hideTimer);
        if (element === current) return;
        current?.classList.remove('is-edit-hover');
        current = element;
        element.classList.add('is-edit-hover');
        chip.href = element.dataset.edit;
        chip.textContent = '✎ Edit: ' + element.dataset.editLabel;
        chip.hidden = false;
        place(element);
    });
    window.addEventListener('scroll', () => { if (current) place(current); }, { passive: true });

    document.addEventListener('click', (event) => {
        if (event.target.closest('.edit-chip, .edit-here__link')) remember();
    });
})();

// ---------------------------------------------------------------------------
// "Copy link" on blog articles: shown only where the browser can copy.
// ---------------------------------------------------------------------------
(() => {
    const button = document.querySelector('[data-copy-link]');
    if (!button || !navigator.clipboard) return;
    const status = document.querySelector('[data-copy-status]');
    button.hidden = false;
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copyLink);
            button.textContent = 'Link copied';
            button.classList.add('is-copied');
            if (status) status.textContent = 'Link copied.';
            setTimeout(() => {
                button.textContent = 'Copy link';
                button.classList.remove('is-copied');
            }, 2500);
        } catch (error) {
            if (status) status.textContent = 'The link could not be copied.';
        }
    });
})();

// ---------------------------------------------------------------------------
// Before-and-after sliders on project pages. Without this script the two
// photos sit side by side. The slider is a real range control, so it works
// by dragging, by touch, and with the arrow keys.
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-compare]').forEach((figure) => {
    const range = figure.querySelector('.compare__range');
    if (!range) return;
    const set = () => figure.style.setProperty('--compare', range.value + '%');
    range.hidden = false;
    figure.classList.add('is-enhanced');
    range.addEventListener('input', set);
    set();
});


// ---------------------------------------------------------------------------
// Butterflies on the menu bar. On the first page of a visit they flutter
// around the bar for 10 seconds, then each one lands in the gap between two
// menu names. Later pages show them already landed. Visitors whose device
// asks for reduced motion see them landed straight away. On phones and
// tablets (menu behind the button) they flutter, then fade away.
// ---------------------------------------------------------------------------
(() => {
    const header = document.querySelector('.site-header');
    const box = header && header.querySelector('.nav-butterflies');
    const items = header ? Array.from(header.querySelectorAll('.primary-nav__item')) : [];
    if (!box || items.length < 2) return;

    const FLIGHT_MS = 10000;
    const colours = ['green', 'gold', 'brown', 'peach'];
    const isDesktop = () => window.matchMedia('(min-width: 1101px)').matches;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let landedBefore = false;
    try { landedBefore = sessionStorage.getItem('vt-butterflies') === 'landed'; } catch (e) { /* storage blocked */ }

    const flies = items.slice(1).map((item, i) => {
        const butterfly = document.createElement('span');
        butterfly.className = 'nav-butterfly nav-butterfly--' + colours[i % colours.length];
        box.appendChild(butterfly);
        return butterfly;
    });

    // Where each butterfly lands: centred in the gap before each menu item after the first.
    const spot = (i) => {
        const base = header.getBoundingClientRect();
        const before = items[i].getBoundingClientRect();
        const after = items[i + 1].getBoundingClientRect();
        return {
            x: (before.right + after.left) / 2 - base.left - flies[i].offsetWidth / 2,
            y: after.top + after.height / 2 - base.top - flies[i].offsetHeight / 2,
        };
    };
    const place = () => {
        if (!isDesktop()) return;
        flies.forEach((butterfly, i) => {
            const s = spot(i);
            butterfly.style.left = s.x + 'px';
            butterfly.style.top = s.y + 'px';
        });
    };
    const land = () => {
        box.classList.remove('is-flying');
        box.classList.add('is-landed');
        place();
    };
    window.addEventListener('resize', place);

    if (landedBefore || reduceMotion || typeof box.animate !== 'function') {
        land();
        return;
    }

    const fly = () => {
        try { sessionStorage.setItem('vt-butterflies', 'landed'); } catch (e) { /* storage blocked */ }
        const desktop = isDesktop();
        const width = header.clientWidth;
        const height = header.clientHeight;
        const random = (min, max) => min + Math.random() * (max - min);
        place();
        box.classList.add('is-flying');

        const flights = flies.map((butterfly, i) => {
            // On phones there is no landing spot: start somewhere in the right half.
            if (!desktop) {
                butterfly.style.left = random(width * 0.45, width * 0.75) + 'px';
                butterfly.style.top = random(4, height - 28) + 'px';
            }
            const home = { x: parseFloat(butterfly.style.left) || 0, y: parseFloat(butterfly.style.top) || 0 };
            // A slow, gentle path near its landing spot (a few turns, short
            // distances, little tilt), as offsets from where it will land.
            const reach = Math.min(160, width * 0.15);
            const frames = [];
            for (let step = 0; step < 3; step++) {
                const x = Math.min(Math.max(home.x + random(-reach, reach), 0), width - 24) - home.x;
                const y = random(2, height - 26) - home.y;
                frames.push({ transform: `translate(${x}px, ${y}px) rotate(${random(-6, 6)}deg)`, opacity: 1 });
            }
            frames.push(desktop
                ? { transform: 'translate(0px, 0px) rotate(0deg) scale(0.8)', opacity: 1 }
                : { transform: frames[2].transform, opacity: 0 });
            return butterfly.animate(frames, { duration: FLIGHT_MS + i * 120, easing: 'ease-in-out' }).finished;
        });
        Promise.all(flights).then(land, land);
    };

    // Wait for the fonts, so the gaps are measured where they will finally be.
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(fly, fly);
    } else {
        fly();
    }
})();


// ---------------------------------------------------------------------------
// Email addresses hidden from spam robots: the page holds the two halves
// reversed, and the readable link is put together here. Without this script
// people still see "name (at) domain".
// ---------------------------------------------------------------------------
document.querySelectorAll('.protected-email').forEach((holder) => {
    const reverse = (text) => Array.from(text || '').reverse().join('');
    const address = reverse(holder.dataset.name) + '@' + reverse(holder.dataset.domain);
    const link = document.createElement('a');
    link.href = 'mailto:' + address;
    link.textContent = address;
    holder.replaceChildren(link);
});
