/**
 * Adjust position and zoom: choose which part of a photo shows in the
 * website's frames, and how far to zoom in. Saved on the picture in the Media
 * Library, so it applies wherever the photo is used.
 *
 * Used on admin screens (image fields, Media Library) and, for signed-in
 * staff, on the website itself: an "Adjust photo" button appears over each
 * framed photo, and the page updates as soon as it is saved.
 *
 * window.vtAdjustPhoto(path, { shape: 'tall' | 'card' | 'wide', report(message) })
 */
(() => {
    const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
    const framingUrl = meta('media-framing-url');
    const csrf = meta('csrf-token');
    if (!framingUrl || !csrf) return;

    const MIN_ZOOM = 1;      // a photo always fills its frame
    const MAX_ZOOM = 3;
    // The frame shapes used on the website, with a typical width on a large
    // screen (CSS pixels) and the largest photo size those frames load.
    const SHAPES = {
        tall: { label: 'Tall frames (home, About, services)', ratio: 3 / 4, width: 440, maxSource: 1200 },
        card: { label: 'Cards and tiles', ratio: 4 / 3, width: 400, maxSource: 1200 },
        wide: { label: 'Wide page headers', ratio: 16 / 7, width: 1600, maxSource: 1920 },
    };

    let ui = null;

    function build() {
        const dialog = document.createElement('dialog');
        dialog.className = 'frame-adjust';
        dialog.setAttribute('aria-labelledby', 'frame-adjust-title');
        dialog.innerHTML = `
            <div class="frame-adjust__head">
                <h2 class="frame-adjust__title" id="frame-adjust-title">Adjust position and zoom</h2>
                <button type="button" class="frame-adjust__close" data-close>Close</button>
            </div>
            <p class="frame-adjust__help">Drag the photo to choose which part shows, or click it and use the arrow keys. Zoom with the slider, the mouse wheel, or two fingers. It applies wherever this photo is used on the website.</p>
            <div class="frame-adjust__previews">
                ${Object.entries(SHAPES).map(([key, shape]) => `
                <figure data-shape="${key}">
                    <div class="frame-adjust__frame frame-adjust__frame--${key}" tabindex="0" role="slider" aria-label="Photo position, ${shape.label}" aria-valuetext=""><img alt=""></div>
                    <figcaption><span class="frame-adjust__here">On this page</span>${shape.label}<span class="frame-adjust__blur" hidden>May look blurry at this zoom</span></figcaption>
                </figure>`).join('')}
            </div>
            <div class="frame-adjust__field">
                <label for="frame-adjust-zoom">Zoom: <output for="frame-adjust-zoom">100%</output></label>
                <input id="frame-adjust-zoom" type="range" min="${MIN_ZOOM * 100}" max="${MAX_ZOOM * 100}" step="5" value="100">
                <p class="frame-adjust__note">100% shows as much of the photo as the frame allows; move right to zoom in on a detail.</p>
            </div>
            <p class="frame-adjust__status" role="status"></p>
            <div class="frame-adjust__buttons">
                <button type="button" class="btn btn--primary btn--small" data-save>Save</button>
                <button type="button" class="btn btn--outline btn--small" data-reset>Centre and reset zoom</button>
                <button type="button" class="btn btn--outline btn--small" data-close>Cancel</button>
            </div>`;
        document.body.append(dialog);

        const previews = dialog.querySelector('.frame-adjust__previews');
        const figures = [...dialog.querySelectorAll('figure')];
        const frames = figures.map((figure) => figure.querySelector('.frame-adjust__frame'));
        const zoom = dialog.querySelector('#frame-adjust-zoom');
        const zoomOut = dialog.querySelector('output');
        const status = dialog.querySelector('.frame-adjust__status');
        const state = { path: '', fx: 50, fy: 50, zoom: 1, width: 0, height: 0, report: null };
        const clampPct = (n) => Math.min(100, Math.max(0, Math.round(n)));
        const clampZoom = (n) => Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, Math.round(n * 100) / 100));

        /** Would this frame have to stretch the photo beyond its own pixels? */
        function blurry(shapeKey) {
            if (!state.width || !state.height) return false;
            const shape = SHAPES[shapeKey];
            const sourceWidth = Math.min(state.width, shape.maxSource);
            const sourceHeight = sourceWidth * state.height / state.width;
            const frameWidth = shape.width;
            const frameHeight = frameWidth / shape.ratio;
            const cover = Math.max(frameWidth / sourceWidth, frameHeight / sourceHeight);
            return cover * state.zoom > 1;
        }

        function apply() {
            frames.forEach((frame) => {
                const image = frame.querySelector('img');
                image.style.setProperty('--fx', state.fx + '%');
                image.style.setProperty('--fy', state.fy + '%');
                image.style.setProperty('--zoom', String(state.zoom));
                frame.setAttribute('aria-valuetext', `${state.fx}% across, ${state.fy}% down, zoom ${Math.round(state.zoom * 100)}%`);
            });
            figures.forEach((figure) => { figure.querySelector('.frame-adjust__blur').hidden = !blurry(figure.dataset.shape); });
            zoom.value = String(Math.round(state.zoom * 100));
            zoomOut.textContent = Math.round(state.zoom * 100) + '%';
        }

        // Drag with a mouse or one finger; pinch with two fingers; wheel to zoom.
        frames.forEach((frame) => {
            const pointers = new Map();
            let start = null;
            frame.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                frame.focus();
                frame.setPointerCapture(event.pointerId);
                pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                start = { fx: state.fx, fy: state.fy, zoom: state.zoom, points: new Map(pointers), box: frame.getBoundingClientRect() };
            });
            frame.addEventListener('pointermove', (event) => {
                if (!pointers.has(event.pointerId) || !start) return;
                pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
                if (pointers.size >= 2 && start.points.size >= 2) {
                    const [a, b] = [...pointers.values()];
                    const [a0, b0] = [...start.points.values()];
                    const now = Math.hypot(a.x - b.x, a.y - b.y);
                    const then = Math.hypot(a0.x - b0.x, a0.y - b0.y) || 1;
                    state.zoom = clampZoom(start.zoom * now / then);
                } else {
                    const first = start.points.get(event.pointerId);
                    if (!first) return;
                    state.fx = clampPct(start.fx - (event.clientX - first.x) / start.box.width * 100 / state.zoom);
                    state.fy = clampPct(start.fy - (event.clientY - first.y) / start.box.height * 100 / state.zoom);
                }
                apply();
            });
            const end = (event) => {
                pointers.delete(event.pointerId);
                start = pointers.size ? { fx: state.fx, fy: state.fy, zoom: state.zoom, points: new Map(pointers), box: frame.getBoundingClientRect() } : null;
            };
            frame.addEventListener('pointerup', end);
            frame.addEventListener('pointercancel', end);
            frame.addEventListener('wheel', (event) => {
                event.preventDefault();
                state.zoom = clampZoom(state.zoom * (event.deltaY < 0 ? 1.05 : 1 / 1.05));
                apply();
            }, { passive: false });
            frame.addEventListener('keydown', (event) => {
                const step = event.shiftKey ? 10 : 2;
                const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };
                if (event.key === '+' || event.key === '=') { state.zoom = clampZoom(state.zoom + 0.05); apply(); event.preventDefault(); return; }
                if (event.key === '-') { state.zoom = clampZoom(state.zoom - 0.05); apply(); event.preventDefault(); return; }
                if (!moves[event.key]) return;
                event.preventDefault();
                state.fx = clampPct(state.fx + moves[event.key][0]);
                state.fy = clampPct(state.fy + moves[event.key][1]);
                apply();
            });
        });
        zoom.addEventListener('input', () => { state.zoom = clampZoom(Number(zoom.value) / 100); apply(); });
        dialog.querySelector('[data-reset]').addEventListener('click', () => { state.fx = 50; state.fy = 50; state.zoom = 1; apply(); });
        dialog.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

        dialog.querySelector('[data-save]').addEventListener('click', async () => {
            status.textContent = 'Saving…';
            try {
                const body = new URLSearchParams({ csrf_token: csrf, path: state.path, fx: state.fx, fy: state.fy, zoom: state.zoom });
                const response = await fetch(framingUrl, { method: 'POST', body, credentials: 'same-origin' });
                const result = await response.json().catch(() => ({ ok: false, message: 'Something went wrong. Please try again.' }));
                if (!result.ok) throw new Error(result.message);
                // Every copy of this photo on the screen follows straight away.
                document.querySelectorAll('img[data-adjust-photo], img[data-framing-preview]').forEach((image) => {
                    if ((image.dataset.adjustPhoto || image.dataset.framingPreview) !== state.path) return;
                    image.style.setProperty('--fx', state.fx + '%');
                    image.style.setProperty('--fy', state.fy + '%');
                    image.style.setProperty('--zoom', String(state.zoom));
                });
                dialog.close();
                state.report?.('Position and zoom saved. They apply wherever this photo is used.');
            } catch (error) {
                status.textContent = error.message;
            }
        });

        async function open(path, options = {}) {
            state.path = path;
            state.report = options.report || null;
            // The frame she came from goes first, marked "On this page".
            figures.forEach((figure) => figure.classList.toggle('is-here', figure.dataset.shape === options.shape));
            const order = options.shape && SHAPES[options.shape] ? [options.shape, ...Object.keys(SHAPES).filter((key) => key !== options.shape)] : Object.keys(SHAPES);
            order.forEach((key) => previews.append(figures.find((figure) => figure.dataset.shape === key)));
            status.textContent = 'Loading…';
            dialog.showModal();
            try {
                const response = await fetch(`${framingUrl}?path=${encodeURIComponent(path)}`, { credentials: 'same-origin' });
                const result = await response.json().catch(() => ({ ok: false, message: 'Something went wrong. Please try again.' }));
                if (!result.ok) throw new Error(result.message);
                frames.forEach((frame) => { frame.querySelector('img').src = result.url; });
                Object.assign(state, { fx: result.fx, fy: result.fy, zoom: clampZoom(result.zoom), width: result.width, height: result.height });
                apply();
                status.textContent = '';
                previews.querySelector('.frame-adjust__frame').focus();
            } catch (error) {
                status.textContent = error.message;
            }
        }
        return { open };
    }

    window.vtAdjustPhoto = (path, options) => {
        if (!path) return;
        ui ??= build();
        ui.open(path, options);
    };

    // ---- On the website, for signed-in staff: "Adjust photo" over each framed photo ----
    const body = document.body;
    if (!body.classList.contains('is-staff')) return;

    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'edit-chip edit-chip--adjust';
    chip.textContent = '⤧ Adjust photo';
    chip.hidden = true;
    body.append(chip);
    let current = null;
    let hideTimer = 0;

    const photoIn = (element) => (element.matches('img[data-adjust-photo]') ? element : element.querySelector('img[data-adjust-photo]'));
    const place = () => {
        const box = current.getBoundingClientRect();
        chip.style.top = `${window.scrollY + box.top + 8}px`;
        chip.style.left = `${Math.max(8, window.scrollX + box.right - chip.offsetWidth - 8)}px`;
    };

    document.addEventListener('mouseover', (event) => {
        if (body.classList.contains('edit-hidden')) return;
        if (event.target.closest('.edit-chip--adjust')) { clearTimeout(hideTimer); return; }
        const holder = event.target.closest('[data-edit], img[data-adjust-photo]');
        const photo = holder ? photoIn(holder) : null;
        if (!photo) { clearTimeout(hideTimer); hideTimer = setTimeout(() => { chip.hidden = true; current = null; }, 250); return; }
        clearTimeout(hideTimer);
        current = holder;
        chip.dataset.path = photo.dataset.adjustPhoto;
        chip.dataset.shape = photo.dataset.adjustShape || '';
        chip.hidden = false;
        place();
    });
    window.addEventListener('scroll', () => { if (current && !chip.hidden) place(); }, { passive: true });
    chip.addEventListener('click', () => {
        window.vtAdjustPhoto(chip.dataset.path, {
            shape: chip.dataset.shape,
            report: (message) => {
                const toast = document.createElement('p');
                toast.className = 'edit-toast';
                toast.setAttribute('role', 'status');
                toast.textContent = message;
                body.append(toast);
                setTimeout(() => toast.remove(), 4000);
            },
        });
    });
})();
