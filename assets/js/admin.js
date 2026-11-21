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

    // ---- Picture chooser: upload a new picture (or drop one in), or pick from the library ----
    // Called with { details: true } (the article editor), it then asks for a
    // description and caption before the picture goes in.
    const describeUrl = meta('media-describe-url');
    let picker = null;
    let pickerResolve = null;
    let pickerOptions = {};

    function buildPicker() {
        const dialog = document.createElement('dialog');
        dialog.className = 'media-picker';
        dialog.setAttribute('aria-labelledby', 'media-picker-title');
        dialog.innerHTML = `
            <div class="media-picker__head">
                <h2 class="media-picker__title" id="media-picker-title">Add a picture</h2>
                <button type="button" class="admin-topbar__logout" data-close>Close</button>
            </div>
            <div class="media-picker__choose">
                <div class="media-drop media-picker__drop"><label>
                    <span class="media-drop__text">Drag a photo here, or <span class="media-drop__link">upload one from your computer</span></span>
                    <span class="media-drop__hint">JPG, PNG, WebP or GIF, up to 10 MB. It is added to the Media Library too.</span>
                    <input class="visually-hidden" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                </label></div>
                <p class="media-picker__or">Or choose one already in the Media Library</p>
                <div class="admin-field">
                    <label for="media-picker-search">Search the library</label>
                    <input id="media-picker-search" type="search" autocomplete="off">
                </div>
                <p class="media-field__status" role="status"></p>
                <ul class="media-grid media-grid--picker"></ul>
                <button type="button" class="btn btn--outline btn--small" data-more hidden>Show more</button>
            </div>
            <form class="media-picker__details" method="dialog" hidden>
                <img class="media-picker__preview" alt="">
                <div>
                    <div class="admin-field">
                        <label for="media-picker-alt">Describe the picture <span class="required" aria-hidden="true">*</span></label>
                        <input id="media-picker-alt" type="text" maxlength="255" required aria-describedby="media-picker-alt-help">
                        <p class="admin-help" id="media-picker-alt-help">For people who can't see it, and for search engines. For example: "A sunny reading corner with ferns on the windowsill".</p>
                    </div>
                    <div class="admin-field">
                        <label for="media-picker-caption">Caption under the picture (optional)</label>
                        <input id="media-picker-caption" type="text" maxlength="250">
                    </div>
                    <p class="media-field__status" role="status" data-details-status></p>
                    <p class="admin-actions">
                        <button type="submit" class="btn btn--primary">Add the picture</button>
                        <button type="button" class="btn btn--outline" data-back>Choose another</button>
                    </p>
                </div>
            </form>`;
        document.body.append(dialog);

        const choose = dialog.querySelector('.media-picker__choose');
        const details = dialog.querySelector('.media-picker__details');
        const grid = dialog.querySelector('.media-grid');
        const status = choose.querySelector('.media-field__status');
        const search = dialog.querySelector('#media-picker-search');
        const more = dialog.querySelector('[data-more]');
        const altInput = dialog.querySelector('#media-picker-alt');
        const captionInput = dialog.querySelector('#media-picker-caption');
        const detailsStatus = dialog.querySelector('[data-details-status]');
        let offset = 0;
        let requestNumber = 0;
        let chosen = null;

        function showChoose() {
            chosen = null;
            details.hidden = true;
            choose.hidden = false;
            dialog.querySelector('#media-picker-title').textContent = 'Add a picture';
        }

        /** A picture was picked or uploaded: finish, or ask for its details first. */
        function picked(item) {
            if (!pickerOptions.details) {
                dialog.close();
                pickerResolve?.(item);
                return;
            }
            chosen = item;
            choose.hidden = true;
            details.hidden = false;
            dialog.querySelector('#media-picker-title').textContent = 'Picture details';
            details.querySelector('img').src = item.thumb || item.url;
            altInput.value = item.alt || '';
            // A description already in the library is used everywhere; it is changed on the Media Library screen.
            altInput.readOnly = Boolean(item.alt);
            dialog.querySelector('#media-picker-alt-help').textContent = item.alt
                ? 'Already described in the Media Library, which is where it can be changed.'
                : 'For people who can\x27t see it, and for search engines. For example: "A sunny reading corner with ferns on the windowsill".';
            captionInput.value = item.caption || '';
            detailsStatus.textContent = item.warning || '';
            (altInput.value ? captionInput : altInput).focus();
        }

        details.addEventListener('submit', async (event) => {
            event.preventDefault();
            const alt = altInput.value.trim();
            if (!alt) {
                detailsStatus.textContent = 'Please describe the picture first.';
                altInput.focus();
                return;
            }
            // A picture without a description in the library gets this one, for use everywhere.
            if (!chosen.alt && describeUrl) {
                try {
                    const body = new URLSearchParams({ csrf_token: csrf, path: chosen.path, alt });
                    await fetch(describeUrl, { method: 'POST', body, credentials: 'same-origin' });
                } catch (error) { /* the picture still goes in */ }
            }
            const item = { ...chosen, alt, caption: captionInput.value.trim() };
            dialog.close();
            pickerResolve?.(item);
        });
        dialog.querySelector('[data-back]').addEventListener('click', showChoose);

        acceptFiles(dialog.querySelector('.media-picker__drop'), async (files) => {
            if (files.length === 0) return;
            status.textContent = `Uploading ${files[0].name}…`;
            try {
                picked(await uploadFile(files[0], pickerOptions.category || ''));
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message;
            }
        });

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
                    button.addEventListener('click', () => picked(item));
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
        // Closed without choosing: the caller gets nothing.
        dialog.addEventListener('close', () => {
            const resolve = pickerResolve;
            pickerResolve = null;
            setTimeout(() => resolve?.(null), 0);
        });

        return { dialog, search, load, showChoose };
    }

    function chooseFromLibrary(options = {}) {
        pickerOptions = options || {};
        picker ??= buildPicker();
        picker.showChoose();
        picker.search.value = '';
        picker.load(true);
        picker.dialog.showModal();
        return new Promise((resolve) => { pickerResolve = resolve; });
    }

    // Shared with the article editor (admin.js below and visual-editor.js).
    window.vtChooseImage = chooseFromLibrary;

    // ---- Adjust position and zoom (assets/js/photo-adjust.js) ----
    function adjustFraming(path, statusElement, shape) {
        window.vtAdjustPhoto?.(path, { shape, report: (message) => { if (statusElement) statusElement.textContent = message; } });
    }

    document.querySelectorAll('[data-adjust-path]').forEach((button) => {
        const status = document.createElement('p');
        status.className = 'media-field__status';
        status.setAttribute('role', 'status');
        button.after(status);
        button.addEventListener('click', () => adjustFraming(button.dataset.adjustPath, status));
    });

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
            if (window.vtAdjustPhoto) {
                const offer = document.createElement('button');
                offer.type = 'button';
                offer.className = 'media-field__offer';
                offer.textContent = 'Adjust how it sits in the frame';
                offer.addEventListener('click', () => adjustFraming(item.path, status, field.dataset.frameShape));
                status.append(' ', offer);
            }
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

        const adjust = field.querySelector('[data-media-adjust]');
        adjust?.addEventListener('click', () => adjustFraming(value.value, status, field.dataset.frameShape));
        new MutationObserver(() => { if (adjust) adjust.hidden = remove.hidden; }).observe(remove, { attributes: true, attributeFilter: ['hidden'] });

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
                insertBlock('[[button ' + words + ' | book-consultation.php]]', 9, 9 + words.length);
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
                const item = await window.vtChooseImage({ details: true, category: location.pathname.includes('blog-edit') ? 'Blog Posts' : '' });
                if (!item) return;
                box.focus();
                box.setSelectionRange(start, end);
                const caption = (item.caption || '').replace(/[|\]]/g, '');
                insertBlock('[[image ' + item.path + (caption ? ' | ' + caption : '') + ']]');
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
            if (editor.dataset.mode === 'visual') return; // the visual editor handles it (visual-editor.js)
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

// ---------------------------------------------------------------------------
// Arriving from an Edit button on the website (#row-Field or #items):
// bring that field into view, highlight it, and put the cursor in it.
// ---------------------------------------------------------------------------
(() => {
    const hash = decodeURIComponent(window.location.hash.slice(1));
    if (!/^(row-[A-Za-z0-9]+|items)$/.test(hash)) return;
    const target = document.getElementById(hash);
    if (!target) return;

    target.classList.add('is-pointed');
    // Inside a closed "More options" group: open it.
    const group = target.closest('details');
    if (group) group.open = true;

    // After "load": the browser's own jump to the #anchor happens first and would undo the focus.
    const arrive = () => {
        target.scrollIntoView({ block: 'center' });
        // The first thing to type in, or for an image the "Choose from library" button.
        const control = target.querySelector('[contenteditable="true"], textarea:not([hidden]), input:not([type="hidden"]):not(.visually-hidden), select, [data-media-library], a.btn');
        if (control) control.focus({ preventScroll: true });
    };
    if (document.readyState === 'complete') {
        arrive();
    } else {
        window.addEventListener('load', () => setTimeout(arrive, 0), { once: true });
    }
})();

// ---------------------------------------------------------------------------
// Character counts under text fields, with a gentle warning past the
// recommended length (the field still saves; it's guidance, not a rule).
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-count-for]').forEach((counter) => {
    const field = document.getElementById(counter.dataset.countFor);
    if (!field) return;
    const ideal = Number(counter.dataset.ideal || 0);
    const update = () => {
        const length = [...field.value].length;
        const over = ideal > 0 && length > ideal;
        counter.classList.toggle('is-over', over);
        counter.textContent = length + (length === 1 ? ' character' : ' characters')
            + (ideal ? (over ? ` · ${length - ideal} over the recommended ${ideal}. ${counter.dataset.idealWhy}` : ` · aim for ${ideal} or fewer`) : '');
    };
    field.addEventListener('input', update);
    update();
});

// ---------------------------------------------------------------------------
// Arrived from the website (an Edit button or "Edit: field" label)? Show a
// "Back to the page" link, and next to each Save button a "Save and return
// to the page" button that saves, then goes back to the same spot.
// ---------------------------------------------------------------------------
(() => {
    let back = null;
    try {
        back = JSON.parse(sessionStorage.getItem('vt-return') || 'null');
    } catch (error) {
        return;
    }
    if (!back || Date.now() - back.time > 2 * 60 * 60 * 1000) return; // two hours

    const goBack = (saved) => {
        try {
            sessionStorage.setItem('vt-restore', JSON.stringify({ url: back.url, scroll: back.scroll, saved }));
            sessionStorage.removeItem('vt-return');
        } catch (error) { /* ignore */ }
        window.location.href = back.url;
    };

    // Saved with "Save and return", and the save worked: go back now.
    try {
        if (sessionStorage.getItem('vt-return-pending') === '1') {
            sessionStorage.removeItem('vt-return-pending');
            const ok = document.querySelector('.admin-alert--success') && !document.querySelector('.admin-alert--error, .admin-error');
            if (ok) {
                goBack(true);
                return;
            }
        }
    } catch (error) { /* ignore */ }

    const content = document.querySelector('.admin-content');
    if (!content) return;

    const bar = document.createElement('p');
    bar.className = 'return-bar';
    const link = document.createElement('a');
    link.href = back.url;
    link.textContent = '← Back to the page: ' + (back.title || 'website');
    link.addEventListener('click', (event) => {
        event.preventDefault();
        goBack(false);
    });
    const forget = document.createElement('button');
    forget.type = 'button';
    forget.className = 'return-bar__forget';
    forget.textContent = 'Hide';
    forget.addEventListener('click', () => {
        try { sessionStorage.removeItem('vt-return'); } catch (error) { /* ignore */ }
        bar.remove();
        document.querySelectorAll('[data-save-return]').forEach((button) => button.remove());
    });
    bar.append(link, forget);
    content.prepend(bar);

    document.querySelectorAll('.admin-form .admin-actions').forEach((actions) => {
        const save = actions.querySelector('button[type="submit"]');
        if (!save) return;
        const button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn btn--outline';
        button.dataset.saveReturn = '';
        button.textContent = 'Save and return to the page';
        button.addEventListener('click', () => {
            try { sessionStorage.setItem('vt-return-pending', '1'); } catch (error) { /* ignore */ }
        });
        save.after(button);
    });
})();

// ---------------------------------------------------------------------------
// Unsaved work is kept in this browser while typing (forms marked
// data-autosave). If the tab closes, the connection drops or the computer
// restarts before saving, the next visit offers to put it back. The copy is
// removed once the form has been saved.
// ---------------------------------------------------------------------------
(() => {
    const KEEP_DAYS = 14;
    document.querySelectorAll('form[data-autosave]').forEach((form) => {
        const key = 'vt-draft:' + form.dataset.autosave;
        const fields = () => [...form.elements].filter((el) => el.name && !['csrf_token', 'publish_action', 'schedule_at', '_delete'].includes(el.name)
            && !['submit', 'button', 'file', 'reset'].includes(el.type));
        const read = () => {
            const data = {};
            fields().forEach((el) => { data[el.name] = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value; });
            return data;
        };
        const store = {
            get: () => { try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (error) { return null; } },
            set: (value) => { try { localStorage.setItem(key, JSON.stringify(value)); } catch (error) { /* storage full or blocked */ } },
            drop: (name = key) => { try { localStorage.removeItem(name); } catch (error) { /* ignore */ } },
        };

        // Just saved successfully? Then the kept copy (of this form, or of the new post it was) is no longer needed.
        try {
            const sent = sessionStorage.getItem('vt-draft-sent');
            if (sent) {
                sessionStorage.removeItem('vt-draft-sent');
                if (document.querySelector('.admin-alert--success') && !document.querySelector('.admin-form .admin-alert--error')) {
                    store.drop(sent);
                    store.drop();
                }
            }
        } catch (error) { /* ignore */ }

        const initial = JSON.stringify(read());
        let timer = 0;
        const save = () => {
            const now = read();
            if (JSON.stringify(now) === initial) store.drop();
            else store.set({ time: Date.now(), data: now });
        };
        const later = () => { clearTimeout(timer); timer = setTimeout(save, 700); };
        form.addEventListener('input', later);
        form.addEventListener('change', later);
        form.addEventListener('submit', () => {
            save();
            try { sessionStorage.setItem('vt-draft-sent', key); } catch (error) { /* ignore */ }
        });

        // Offer to put back a copy that differs from what is on screen.
        const kept = store.get();
        if (!kept || !kept.data) return;
        if (Date.now() - kept.time > KEEP_DAYS * 86400000 || JSON.stringify(kept.data) === initial) {
            store.drop();
            return;
        }
        const when = new Date(kept.time).toLocaleString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });
        const bar = document.createElement('div');
        bar.className = 'admin-alert admin-alert--error autosave-bar';
        bar.setAttribute('role', 'alert');
        bar.innerHTML = '<p>You have changes from <strong></strong> that were not saved.</p>'
            + '<p class="autosave-bar__actions"><button type="button" class="btn btn--primary btn--small" data-restore>Put them back</button> '
            + '<button type="button" class="btn btn--outline btn--small" data-discard>Discard them</button></p>';
        bar.querySelector('strong').textContent = when;
        form.before(bar);

        bar.querySelector('[data-restore]').addEventListener('click', () => {
            fields().forEach((el) => {
                if (!(el.name in kept.data)) return;
                if (el.type === 'checkbox') el.checked = kept.data[el.name] === '1';
                else el.value = kept.data[el.name];
                el.dispatchEvent(new Event('vt:restored'));
                el.dispatchEvent(new Event('input', { bubbles: true }));
            });
            bar.className = 'admin-alert admin-alert--success autosave-bar';
            bar.setAttribute('role', 'status');
            bar.textContent = 'Your changes are back. Remember to save.';
        });
        bar.querySelector('[data-discard]').addEventListener('click', () => {
            store.drop();
            bar.remove();
        });
    });
})();

// ---------------------------------------------------------------------------
// "How it may look in Google" (cms_seo_panel() in cms.php): a preview of the
// search result and a short checklist in plain words, updated while typing.
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-seo]').forEach((panel) => {
    let config;
    try { config = JSON.parse(panel.dataset.seo); } catch (error) { return; }
    const form = panel.closest('form');
    const value = (name) => {
        const field = form?.elements[name];
        if (field && typeof field.value === 'string' && !(field instanceof RadioNodeList)) return field.value;
        return config.values?.[name] ?? '';
    };
    const first = (names) => (names || []).map((name) => value(name).trim()).find(Boolean) || '';
    // The words a reader sees: formatting marks, pictures and buttons left out.
    const plain = (text) => text.replace(/\[\[[^\]]*\]\]/g, ' ').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1').replace(/[*_#>]/g, ' ').replace(/\s+/g, ' ').trim();
    const slugify = (text) => text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    const cut = (text, max) => (text.length > max ? text.slice(0, max - 1).replace(/\s+\S*$/, '') + ' …' : text);
    const site = panel.querySelector('.seo-check__site');
    const title = panel.querySelector('.seo-check__title');
    const description = panel.querySelector('.seo-check__description');
    const list = panel.querySelector('.seo-check__list');

    const update = () => {
        const slug = value('Slug').trim() || (config.slugFrom ? slugify(value(config.slugFrom)) : (config.values?.Slug || ''));
        const address = config.site + (config.path || '').replace('{Slug}', encodeURIComponent(slug));
        const own = first(config.title) || (config.home ? '' : config.fallbackTitle || '');
        const fullTitle = own ? own + ' | ' + config.brand : config.brand + ' | ' + config.tagline;
        const words = plain(first(config.description) || config.fallbackDescription || '');

        site.textContent = config.brand + ' · ' + address.replace(/^https?:\/\//, '').replace(/\/$/, '').replace(/\//g, ' › ');
        title.textContent = cut(fullTitle, 62);
        description.textContent = words ? cut(words, 158) : 'No description yet: Google will pick some words from the page.';
        description.classList.toggle('is-missing', !words);

        const checks = [];
        const add = (ok, good, fix) => checks.push([ok, ok ? good : fix]);
        if (config.home && !own) {
            add(true, 'Title: the business name and tagline.', '');
        } else {
            add(own !== '' && own.length <= 60, `Title: ${own.length} characters.`, own === '' ? 'Add a title.' : `Title: ${own.length} characters. Search results cut off long titles: aim for 60 or fewer.`);
        }
        const length = words.length;
        add(length >= 70 && length <= 160, `Description: ${length} characters.`,
            length === 0 ? 'Add a search description (or a summary) of one or two sentences, so you choose what Google shows.'
                : length < 70 ? `Description: ${length} characters. A little short: aim for 70 to 155.`
                    : `Description: ${length} characters. Search results cut it off: aim for 155 or fewer.`);
        if (config.image) {
            const picture = value(config.image).trim() !== '';
            add(picture, 'Has a picture, shown when the page is shared.', 'Add a picture: it is shown when the page is shared on WhatsApp, Facebook or LinkedIn, and in some search results.');
            if (picture && config.alt) {
                add(value(config.alt).trim() !== '', 'The picture is described.', 'Describe the picture (alt text): search engines read it, and it helps people who cannot see it.');
            }
        }
        if (config.content) {
            const count = plain(value(config.content)).split(' ').filter(Boolean).length;
            const minimum = config.minWords || 0;
            add(count >= minimum, `${count} words.`, `${count} words. Pages with more to say are found more often: aim for at least ${minimum}.`);
        }
        if (config.heading) {
            add(/^#{2,3}\s/m.test(value(config.content)), 'Has subheadings, so readers and search engines can see its parts.', 'Add a subheading or two (Heading button): it helps readers skim and search engines understand the article.');
        }
        if (config.link) {
            const text = value(config.content);
            add(/\]\((?!https?:|mailto:|tel:)[^)\s]+\)/.test(text) || /\[\[button [^|\]]+\|\s*(?!https?:)[^\]\s]+\s*\]\]/.test(text),
                'Links to another page on the website.', 'Add a link to a service or another article: it helps readers and search engines find more.');
        }
        if (config.category) {
            add(value(config.category).trim() !== '', 'Has a category.', 'Choose a category, so the article also appears on its category page.');
        }

        list.replaceChildren(...checks.map(([ok, text]) => {
            const item = document.createElement('li');
            item.className = ok ? 'is-good' : 'is-todo';
            const label = document.createElement('span');
            label.className = 'seo-check__mark';
            label.textContent = ok ? 'Good' : 'To improve';
            item.append(label, ' ', text);
            return item;
        }));
    };

    let timer = 0;
    const later = () => { clearTimeout(timer); timer = setTimeout(update, 250); };
    form?.addEventListener('input', later);
    form?.addEventListener('change', later);
    // Pictures chosen from the library change hidden fields without an event.
    setInterval(() => { if (!document.hidden) update(); }, 2000);
    update();
});

// ---------------------------------------------------------------------------
// Email previews (campaigns and the welcome email): Computer / Phone width,
// and the frame grows to fit the email. Kept here rather than in the page,
// so the security policy can refuse any script written into a page.
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-preview-widths]').forEach((box) => {
    const frame = box.querySelector('iframe');
    box.querySelectorAll('[data-width]').forEach((button) => button.addEventListener('click', () => {
        frame.style.width = button.dataset.width + 'px';
        box.querySelectorAll('[data-width]').forEach((b) => {
            b.classList.toggle('is-active', b === button);
            b.setAttribute('aria-pressed', String(b === button));
        });
    }));
    frame.addEventListener('load', () => {
        try { frame.style.height = (frame.contentDocument.documentElement.scrollHeight + 20) + 'px'; } catch (e) { frame.style.height = '1600px'; }
    });
});

// ---------------------------------------------------------------------------
// My account: the QR code for the authenticator app (drawn here from the
// otpauth address, by assets/js/lib/qrcode.js), a confirmation before a
// button with data-confirm, and printing the backup codes.
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-qr]').forEach((box) => {
    if (typeof window.qrcode !== 'function') return;
    const qr = window.qrcode(0, 'M');
    qr.addData(box.dataset.qr);
    qr.make();
    box.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 3, scalable: true });
});
document.querySelectorAll('button[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
        if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
});
document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
