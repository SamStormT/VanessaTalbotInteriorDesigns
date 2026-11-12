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
                    image.src = item.thumb || item.url;
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

    // Shared with the article editor below.
    window.vtChooseImage = chooseFromLibrary;

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
            image.src = item.thumb || item.url;
            preview.hidden = false;
            remove.hidden = false;
            if (alt && !alt.value && item.alt) alt.value = item.alt;
            if (caption && !caption.value && item.caption) caption.value = item.caption;
            status.textContent = (item.warning ? item.warning + ' ' : '') + (alt && !alt.value
                ? 'Added. Please describe the image below, then save.'
                : 'Added. Remember to save.');
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

// ---------------------------------------------------------------------------
// Article editor: toolbar buttons insert simple formatting marks into the
// text box (see includes/rich-text.php), and Preview shows how it will look.
// ---------------------------------------------------------------------------
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
        const box = editor.querySelector('textarea');
        const toolbar = editor.querySelector('.rich-toolbar');
        const preview = editor.querySelector('.rich-preview');
        const previewButton = editor.querySelector('[data-rich="preview"]');
        toolbar.hidden = false;

        const selection = () => box.value.slice(box.selectionStart, box.selectionEnd);

        /** Selected words for bold, links, headings and buttons: one line at most.
            With several lines selected, insert after them instead. */
        const selected = () => {
            const text = selection();
            if (text.includes('\n')) {
                box.setSelectionRange(box.selectionEnd, box.selectionEnd);
                return '';
            }
            return text;
        };

        /** Replace the selection with text, then select part of it (or place the cursor). */
        function insert(text, selectFrom = text.length, selectTo = selectFrom) {
            const start = box.selectionStart;
            box.setRangeText(text, start, box.selectionEnd, 'end');
            box.focus();
            box.setSelectionRange(start + selectFrom, start + selectTo);
            box.dispatchEvent(new Event('input'));
        }

        /** Text that must sit on its own lines: add line breaks around it if needed. */
        function insertBlock(text, selectFrom, selectTo) {
            const before = box.value.slice(0, box.selectionStart);
            const after = box.value.slice(box.selectionEnd);
            const lead = before === '' || before.endsWith('\n\n') ? '' : (before.endsWith('\n') ? '\n' : '\n\n');
            const tail = after === '' || after.startsWith('\n\n') ? '' : (after.startsWith('\n') ? '\n' : '\n\n');
            const from = selectFrom ?? text.length;
            insert(lead + text + tail, lead.length + from, lead.length + (selectTo ?? from));
        }

        /** Words typed straight after a [[picture]], [[button]] or [[section]] line
            would break it, so start a new paragraph first. */
        function leaveBlockLine() {
            const lastLine = box.value.slice(0, box.selectionStart).split('\n').pop();
            if (/^\s*\[\[.*\]\]\s*$/.test(lastLine)) {
                insert('\n\n');
            }
        }

        /** Wrap the selection in marks, or insert sample words to type over. */
        function wrap(mark, sample) {
            leaveBlockLine();
            const words = selected() || sample;
            insert(mark + words + mark, mark.length, mark.length + words.length);
        }

        /** Start each selected line with a prefix (for lists). */
        function prefixLines(prefix, sample) {
            const text = selection() || sample; // lists work on whole lines
            const lines = text.split('\n').map((line) => (line.trim() === '' ? line : prefix + line.replace(/^\s*(?:[-*•]|>)\s+/, '')));
            const joined = lines.join('\n');
            insertBlock(joined, 0, joined.length);
        }

        const actions = {
            bold: () => wrap('**', 'bold words'),
            italic: () => wrap('_', 'italic words'),
            heading: () => {
                const words = selected() || 'Heading';
                insertBlock('## ' + words, 3, 3 + words.length);
            },
            list: () => prefixLines('- ', 'First point\nSecond point'),
            quote: () => {
                const words = selected() || 'A sentence worth remembering.';
                insertBlock('> ' + words + '\n> — Name', 2, 2 + words.length);
            },
            link: () => {
                const address = window.prompt('Link to which page? For example contact.php, or a full https:// address.', 'https://');
                if (!address || address.trim() === 'https://') return;
                leaveBlockLine();
                const words = selected() || 'link words';
                insert('[' + words + '](' + address.trim() + ')', 1, 1 + words.length);
            },
            button: () => {
                const words = selected() || 'Book a consultation';
                insertBlock('[[button ' + words + ' | contact.php]]', 9, 9 + words.length);
            },
            section: () => {
                const answer = (window.prompt('Section colour: peach, green, gold or white', 'peach') || 'peach').trim().toLowerCase();
                const style = ['peach', 'green', 'gold', 'white'].includes(answer) ? answer : 'peach';
                const words = selection() || 'Text inside the highlighted section.'; // may be several lines
                const open = '[[section ' + style + ']]\n';
                insertBlock(open + words + '\n[[/section]]', open.length, open.length + words.length);
            },
            more: () => insertBlock('[[more]]'),
            image: async () => {
                if (!window.vtChooseImage) return;
                const start = box.selectionStart;
                const end = box.selectionEnd;
                const item = await window.vtChooseImage();
                if (!item) return;
                box.focus();
                box.setSelectionRange(start, end);
                insertBlock('[[image ' + item.path + (item.caption ? ' | ' + item.caption : '') + ']]');
            },
            preview: async () => {
                if (previewButton.getAttribute('aria-pressed') === 'true') {
                    preview.hidden = true;
                    box.hidden = false;
                    previewButton.setAttribute('aria-pressed', 'false');
                    previewButton.textContent = 'Preview';
                    box.focus();
                    return;
                }
                preview.innerHTML = '<p><em>Loading preview…</em></p>';
                preview.hidden = false;
                box.hidden = true;
                previewButton.setAttribute('aria-pressed', 'true');
                previewButton.textContent = 'Back to editing';
                try {
                    const body = new URLSearchParams({ csrf_token: csrf, text: box.value });
                    const response = await fetch(editor.dataset.previewUrl, { method: 'POST', body, credentials: 'same-origin' });
                    const result = await response.json();
                    if (result.ok) {
                        preview.innerHTML = result.html;
                    } else {
                        preview.textContent = result.message || 'Preview not available.';
                    }
                } catch (error) {
                    preview.textContent = 'Preview not available. Please try again.';
                }
            },
        };

        toolbar.addEventListener('click', (event) => {
            const button = event.target.closest('[data-rich]');
            if (!button || !actions[button.dataset.rich]) return;
            if (button.dataset.rich !== 'preview' && previewButton.getAttribute('aria-pressed') === 'true') {
                actions.preview(); // back to editing first
            }
            actions[button.dataset.rich]();
        });

        // Keyboard shortcuts for the two most common marks.
        box.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey)) return;
            if (event.key === 'b') { event.preventDefault(); actions.bold(); }
            if (event.key === 'i') { event.preventDefault(); actions.italic(); }
        });
    });
})();

// ---------------------------------------------------------------------------
// Newsletter sending: while the campaign page is open, send batch after
// batch and show progress. Stops at the hourly limit or on an error, with a
// Resume button; reloads the page when everything has been sent.
// ---------------------------------------------------------------------------
(() => {
    const panel = document.querySelector('[data-campaign-progress]');
    if (!panel || panel.dataset.sending !== '1') return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const bar = panel.querySelector('progress');
    const text = panel.querySelector('[data-progress-text]');
    const help = panel.querySelector('[data-progress-help]');
    const resume = panel.querySelector('[data-progress-resume]');
    let running = false;

    const warnOnLeave = (event) => { event.preventDefault(); event.returnValue = ''; };

    function show(result) {
        const finished = result.sent + result.failed + result.skipped;
        bar.max = result.total;
        bar.value = finished;
        text.textContent = `${result.sent} sent · ${result.failed} failed · ${result.skipped} skipped (unsubscribed while sending) · ${result.pending + result.sending} waiting`;
    }

    function stop(message) {
        running = false;
        window.removeEventListener('beforeunload', warnOnLeave);
        help.textContent = message;
        resume.hidden = false;
    }

    async function run() {
        if (running) return;
        running = true;
        resume.hidden = true;
        help.textContent = 'Sending… please keep this page open.';
        window.addEventListener('beforeunload', warnOnLeave);

        while (running) {
            let result;
            try {
                const body = new URLSearchParams({ csrf_token: csrf, id: panel.dataset.campaignId });
                const response = await fetch(panel.dataset.campaignProgress, { method: 'POST', body, credentials: 'same-origin' });
                result = await response.json();
            } catch (error) {
                stop('The connection was interrupted. Press Resume to carry on; nobody will be emailed twice.');
                return;
            }
            if (!result.ok) {
                stop(result.message);
                return;
            }
            show(result);
            if (result.done) {
                window.removeEventListener('beforeunload', warnOnLeave);
                help.textContent = result.message;
                window.location.reload();
                return;
            }
            if (result.paused) {
                stop(result.message);
                return;
            }
            await new Promise((resolve) => setTimeout(resolve, 1500)); // be gentle with the mail server
        }
    }

    resume.addEventListener('click', run);
    run();
})();
