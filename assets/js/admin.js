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

// ---------------------------------------------------------------------------
// Media: drag and drop uploads, and the library picker
// ---------------------------------------------------------------------------
(() => {
    const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
    const csrf = meta('csrf-token');
    const uploadUrl = meta('media-upload-url');
    const listUrl = meta('media-list-url');
    const MAX_BYTES = 10 * 1048576;

    if (!uploadUrl) return;

    async function readJson(response) {
        try {
            return await response.json();
        } catch {
            throw new Error('Something went wrong. Please try again.');
        }
    }

    async function uploadFile(file, category) {
        if (file.size > MAX_BYTES) {
            throw new Error(`${file.name} is too large. The limit is 10 MB.`);
        }
        const body = new FormData();
        body.append('csrf_token', csrf);
        body.append('category', category || '');
        body.append('file', file);

        const response = await fetch(uploadUrl, { method: 'POST', body, credentials: 'same-origin' });
        const result = await readJson(response);
        if (!result.ok) throw new Error(result.message);
        return result.item;
    }

    /** Make an element accept dropped files, and wire its file input. */
    function acceptFiles(zone, onFiles) {
        const input = zone.querySelector('input[type="file"]');

        ['dragenter', 'dragover'].forEach((name) => zone.addEventListener(name, (event) => {
            event.preventDefault();
            zone.classList.add('is-dragging');
        }));
        ['dragleave', 'dragend'].forEach((name) => zone.addEventListener(name, () => zone.classList.remove('is-dragging')));
        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            zone.classList.remove('is-dragging');
            onFiles([...event.dataTransfer.files]);
        });
        input.addEventListener('focus', () => zone.classList.add('is-focused'));
        input.addEventListener('blur', () => zone.classList.remove('is-focused'));
        input.addEventListener('change', () => {
            onFiles([...input.files]);
            input.value = '';
        });
    }

    // A file dropped outside a drop zone must not replace the page and lose work.
    ['dragover', 'drop'].forEach((name) => window.addEventListener(name, (event) => event.preventDefault()));

    // ---- Library picker ----
    let picker = null;
    let pickerResolve = null;

    function buildPicker() {
        const dialog = document.createElement('dialog');
        dialog.className = 'media-picker';
        dialog.setAttribute('aria-label', 'Choose an image');
        dialog.innerHTML = `
            <div class="media-picker__head">
                <h2 class="media-picker__title">Choose an image</h2>
                <button type="button" class="admin-topbar__logout" data-close>Close</button>
            </div>
            <div class="admin-field">
                <label for="media-picker-search">Search</label>
                <input id="media-picker-search" type="search" autocomplete="off">
            </div>
            <p class="media-field__status" role="status"></p>
            <ul class="media-grid media-grid--picker"></ul>
            <button type="button" class="btn btn--outline btn--small" data-more hidden>Show more</button>`;
        document.body.append(dialog);

        const grid = dialog.querySelector('.media-grid');
        const status = dialog.querySelector('.media-field__status');
        const search = dialog.querySelector('input');
        const more = dialog.querySelector('[data-more]');
        let offset = 0;
        let requestNumber = 0;

        async function load(reset) {
            const thisRequest = ++requestNumber;
            if (reset) offset = 0;
            status.textContent = 'Loading…';
            try {
                const response = await fetch(`${listUrl}?q=${encodeURIComponent(search.value)}&offset=${offset}`, { credentials: 'same-origin' });
                const result = await readJson(response);
                if (thisRequest !== requestNumber) return;
                if (!result.ok) throw new Error(result.message);

                if (reset) grid.replaceChildren();
                result.items.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'media-card__link';
                    const frame = document.createElement('span');
                    frame.className = 'media-card__preview';
                    const image = document.createElement('img');
                    image.src = item.url;
                    image.alt = '';
                    image.loading = 'lazy';
                    frame.append(image);
                    const name = document.createElement('span');
                    name.className = 'media-card__name';
                    name.textContent = item.name;
                    button.append(frame, name);
                    button.addEventListener('click', () => {
                        dialog.close();
                        pickerResolve?.(item);
                    });
                    const row = document.createElement('li');
                    row.className = 'media-card';
                    row.append(button);
                    grid.append(row);
                });
                offset += result.items.length;
                more.hidden = offset >= result.total;
                status.textContent = result.total === 0 ? 'No images found.' : '';
            } catch (error) {
                if (thisRequest === requestNumber) status.textContent = error.message;
            }
        }

        let timer;
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => load(true), 250);
        });
        more.addEventListener('click', () => load(false));
        dialog.querySelector('[data-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });

        return { dialog, search, load };
    }

    function chooseFromLibrary() {
        picker ??= buildPicker();
        picker.search.value = '';
        picker.load(true);
        picker.dialog.showModal();
        return new Promise((resolve) => { pickerResolve = resolve; });
    }

    // ---- Image field on a form ----
    document.querySelectorAll('[data-media-field]').forEach((field) => {
        const value = field.querySelector('[data-media-value]');
        const preview = field.querySelector('.media-field__preview');
        const image = preview.querySelector('img');
        const remove = field.querySelector('[data-media-remove]');
        const status = field.querySelector('.media-field__status');
        const alt = document.getElementById(field.dataset.altTarget || '');
        const caption = document.getElementById(field.dataset.captionTarget || '');

        function show(item) {
            value.value = item.path;
            image.src = item.url;
            preview.hidden = false;
            remove.hidden = false;
            if (alt && !alt.value && item.alt) alt.value = item.alt;
            if (caption && !caption.value && item.caption) caption.value = item.caption;
            status.textContent = alt && !alt.value
                ? 'Added. Please describe the image below, then save.'
                : 'Added. Remember to save.';
        }

        acceptFiles(field.querySelector('.media-drop'), async (files) => {
            if (files.length === 0) return;
            status.textContent = `Uploading ${files[0].name}…`;
            try {
                show(await uploadFile(files[0], field.dataset.category));
            } catch (error) {
                status.textContent = error.message;
            }
        });

        field.querySelector('[data-media-library]').addEventListener('click', async () => {
            const item = await chooseFromLibrary();
            if (item) show(item);
        });

        remove.addEventListener('click', () => {
            value.value = '';
            image.removeAttribute('src');
            preview.hidden = true;
            remove.hidden = true;
            status.textContent = 'Removed. Remember to save.';
        });
    });

    /** Upload several files one after another, reporting progress. */
    async function uploadAll(files, status, category, afterEach) {
        let done = 0;
        const problems = [];
        for (const file of files) {
            status.textContent = `Uploading ${done + 1} of ${files.length}…`;
            try {
                const item = await uploadFile(file, category);
                if (afterEach) await afterEach(item);
            } catch (error) {
                problems.push(error.message);
            }
            done += 1;
        }
        return problems;
    }

    function finish(status, problems) {
        if (problems.length === 0) {
            window.location.reload();
        } else {
            status.textContent = problems.join(' ');
        }
    }

    // ---- Media Library page: many files at once ----
    document.querySelectorAll('[data-library-drop]').forEach((zone) => {
        const status = zone.querySelector('.media-field__status');
        acceptFiles(zone, async (files) => {
            if (files.length) finish(status, await uploadAll(files, status, ''));
        });
    });

    // ---- Project gallery: many files at once, each becoming a gallery image ----
    document.querySelectorAll('[data-gallery-drop]').forEach((zone) => {
        const status = zone.querySelector('.media-field__status');
        acceptFiles(zone, async (files) => {
            if (files.length === 0) return;
            finish(status, await uploadAll(files, status, zone.dataset.category, async (item) => {
                const body = new URLSearchParams({ csrf_token: csrf, ImagePath: item.path, AltText: item.alt, Caption: item.caption });
                await fetch(zone.dataset.postUrl, { method: 'POST', body, credentials: 'same-origin' });
            }));
        });
    });
})();
