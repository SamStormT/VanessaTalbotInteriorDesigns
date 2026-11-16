// ---------------------------------------------------------------------------
// Visual article editor: bold looks bold, pictures show as pictures, and a
// coloured section shows in its colour. What is saved is still the simple
// text-with-marks format of includes/rich-text.php (so the website and the
// newsletters render it exactly as before); this editor turns that text
// into an editable page and back again as you type.
//
// "Text with codes" switches back to the plain box with the marks, for
// anything the visual view can't do. The choice is remembered.
// ---------------------------------------------------------------------------
(() => {
    const STYLES = ['peach', 'green', 'gold', 'white'];
    const esc = (text) => String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    // ---- Text with marks → editable page (mirrors rich_blocks() in PHP) ----

    function marks(html) {
        html = html.replace(/\*\*(?=\S)(.+?)(?<=\S)\*\*/g, '<strong>$1</strong>');
        return html.replace(/(?<![\w&#])_(?=\S)(.+?)(?<=\S)_(?!\w)/g, '<em>$1</em>');
    }

    function inline(text) {
        const links = [];
        const held = text.replace(/\[([^\]\n]+)\]\(([^)\s]+)\)/g, (match, label, href) => {
            links.push([label, href]);
            return '\u0001' + (links.length - 1) + '\u0002';
        });
        return marks(esc(held)).replace(/\u0001(\d+)\u0002/g, (match, index) => {
            const [label, href] = links[Number(index)];
            return '<a href="' + esc(href) + '">' + marks(esc(label)) + '</a>';
        });
    }

    const tools = (name, change = true) => '<span class="ve-tools" contenteditable="false">'
        + (change ? '<button type="button" class="ve-tool" data-ve="edit">Change</button>' : '')
        + '<button type="button" class="ve-tool" data-ve="remove" aria-label="Remove ' + esc(name) + '">Remove</button></span>';

    const widgets = {
        image: (path, caption, base) => '<figure class="rich-figure ve-widget" contenteditable="false" data-image="' + esc(path) + '" data-caption="' + esc(caption) + '">'
            + '<img src="' + esc(base + path) + '" alt="">'
            + '<figcaption>' + (caption ? esc(caption) : '<span class="ve-hint">No caption</span>') + '</figcaption>' + tools('picture') + '</figure>',
        button: (label, link) => '<p class="rich-button ve-widget" contenteditable="false" data-button="' + esc(label) + '" data-link="' + esc(link) + '">'
            + '<span class="btn btn--primary">' + esc(label) + '</span> <span class="ve-hint">goes to ' + esc(link) + '</span>' + tools('button') + '</p>',
        more: () => '<div class="ve-more ve-widget" contenteditable="false" data-more>'
            + '<span>Newsletters show the article up to here, then a "Read more" button</span>' + tools('read more point', false) + '</div>',
        section: (style, inner) => '<div class="rich-section rich-section--' + style + '" data-section="' + style + '">'
            + '<span class="ve-section-label" contenteditable="false">' + style.charAt(0).toUpperCase() + style.slice(1) + ' section'
            + '<button type="button" class="ve-tool" data-ve="colour">Colour</button><button type="button" class="ve-tool" data-ve="unwrap">Remove colour</button></span>'
            + (inner || '<p><br></p>') + '</div>',
    };

    function blocks(lines, base) {
        let html = '';
        let para = [];
        let list = null;
        const flush = () => {
            if (para.length) {
                html += '<p>' + para.map(inline).join('<br>') + '</p>';
                para = [];
            }
            if (list) {
                html += '<' + list.type + '>' + list.items.map((item) => '<li>' + inline(item) + '</li>').join('') + '</' + list.type + '>';
                list = null;
            }
        };

        for (let i = 0; i < lines.length; i++) {
            const t = lines[i].trim();
            let m;
            if (t === '') { flush(); continue; }
            if ((m = t.match(/^\[\[section(?:\s+([a-z]+))?\]\]$/))) {
                flush();
                const style = STYLES.includes(m[1]) ? m[1] : 'peach';
                const inner = [];
                let depth = 1;
                for (i++; i < lines.length; i++) {
                    const u = lines[i].trim();
                    if (/^\[\[section\b/.test(u)) depth++;
                    else if (u === '[[/section]]' && --depth === 0) break;
                    inner.push(lines[i]);
                }
                html += widgets.section(style, blocks(inner, base));
                continue;
            }
            if (t === '[[/section]]') continue;
            if (t === '[[more]]') { flush(); html += widgets.more(); continue; }
            if ((m = t.match(/^\[\[image\s+([^\]|]+?)\s*(?:\|\s*(.*?))?\]\]$/))) { flush(); html += widgets.image(m[1].trim(), (m[2] || '').trim(), base); continue; }
            if ((m = t.match(/^\[\[button\s+(.+?)\s*\|\s*(\S+?)\s*\]\]$/))) { flush(); html += widgets.button(m[1], m[2]); continue; }
            if ((m = t.match(/^(#{2,3})\s+(.+)$/))) {
                flush();
                const tag = m[1].length === 2 ? 'h2' : 'h3';
                html += '<' + tag + '>' + inline(m[2]) + '</' + tag + '>';
                continue;
            }
            if (t.startsWith('>')) {
                flush();
                const quote = [];
                let author = '';
                for (; i < lines.length && lines[i].trim().startsWith('>'); i++) {
                    const q = lines[i].trim().slice(1).trim();
                    const who = q.match(/^(?:—|–|--|-)\s*(.+)$/);
                    if (who) author = who[1];
                    else if (q !== '') quote.push(q);
                }
                i--;
                if (quote.length) {
                    html += '<blockquote><p>' + quote.map(inline).join('<br>') + '</p>' + (author ? '<p>— ' + esc(author) + '</p>' : '') + '</blockquote>';
                }
                continue;
            }
            const bullet = t.match(/^(?:[-*•])\s+(.+)$/u);
            const numbered = !bullet && t.match(/^\d+[.)]\s+(.+)$/);
            if (bullet || numbered) {
                const type = numbered ? 'ol' : 'ul';
                if (para.length || (list && list.type !== type)) flush();
                list ??= { type, items: [] };
                list.items.push((numbered || bullet)[1]);
                continue;
            }
            if (list) flush();
            para.push(t);
        }
        flush();
        return html;
    }

    function toHtml(text, base = '/') {
        return blocks(String(text).replace(/\r\n?/g, '\n').split('\n'), base);
    }

    // ---- Editable page → text with marks ----

    const INLINE = /^(A|B|STRONG|I|EM|U|S|SPAN|FONT|SMALL|MARK|CODE|SUB|SUP|BR|IMG)$/;
    const skip = (el) => el.classList.contains('ve-tools') || el.classList.contains('ve-section-label');
    const oneLine = (text) => text.replace(/\s*\n\s*/g, ' ').replace(/[ \t]+/g, ' ').trim();

    function wrap(text, mark) {
        return text.split('\n').map((line) => {
            const core = line.trim();
            if (!core) return line;
            const lead = line.match(/^\s*/)[0];
            const trail = line.match(/\s*$/)[0];
            return lead + mark + core + mark + trail;
        }).join('\n');
    }

    function inlineOut(nodes) {
        let out = '';
        for (const node of nodes) {
            if (node.nodeType === 3) {
                out += node.nodeValue.replace(/ /g, ' ').replace(/[\r\n]+/g, ' ');
                continue;
            }
            if (node.nodeType !== 1 || skip(node)) continue;
            const tag = node.tagName;
            if (tag === 'BR') { out += '\n'; continue; }
            if (tag === 'IMG') continue;
            let text = inlineOut(node.childNodes);
            if (tag === 'A') {
                const href = (node.getAttribute('href') || '').trim();
                const label = oneLine(text).replace(/[[\]]/g, '');
                out += href && label ? '[' + label + '](' + href.replace(/\s/g, '%20') + ')' : text;
                continue;
            }
            const weight = node.style.fontWeight;
            const bold = tag === 'B' || tag === 'STRONG' || weight === 'bold' || Number(weight) >= 600;
            const italic = tag === 'I' || tag === 'EM' || node.style.fontStyle === 'italic';
            if (text.trim() && italic) text = wrap(text, '_');
            if (text.trim() && bold) text = wrap(text, '**');
            if (!INLINE.test(tag)) text = '\n' + text + '\n'; // a block inside a paragraph or quote
            out += text;
        }
        return out;
    }

    /** Paragraph text: single line breaks kept, blank lines and stray spaces tidied. */
    const paragraph = (text) => text.split('\n').map((line) => line.replace(/[ \t]+/g, ' ').trim()).join('\n').replace(/\n{2,}/g, '\n').trim();

    function listItems(list) {
        const items = [];
        for (const li of list.children) {
            if (li.tagName !== 'LI') continue;
            const own = [...li.childNodes].filter((n) => !(n.nodeType === 1 && /^(UL|OL)$/.test(n.tagName)));
            const text = oneLine(inlineOut(own));
            if (text) items.push(text);
            li.querySelectorAll(':scope > ul, :scope > ol').forEach((inner) => items.push(...listItems(inner)));
        }
        return items;
    }

    function blocksOut(container) {
        const out = [];
        let loose = [];
        const flushLoose = () => {
            const text = paragraph(inlineOut(loose));
            if (text) out.push(text);
            loose = [];
        };
        for (const node of container.childNodes) {
            if (node.nodeType === 3 || (node.nodeType === 1 && INLINE.test(node.tagName) && !node.classList.contains('ve-widget'))) {
                loose.push(node);
                continue;
            }
            if (node.nodeType !== 1 || skip(node)) continue;
            flushLoose();
            const el = node;
            const tag = el.tagName;
            if (el.hasAttribute('data-image')) {
                out.push('[[image ' + el.dataset.image + (el.dataset.caption ? ' | ' + el.dataset.caption : '') + ']]');
            } else if (el.hasAttribute('data-button')) {
                out.push('[[button ' + el.dataset.button + ' | ' + el.dataset.link + ']]');
            } else if (el.hasAttribute('data-more')) {
                out.push('[[more]]');
            } else if (el.hasAttribute('data-section')) {
                out.push('[[section ' + el.dataset.section + ']]\n' + blocksOut(el).join('\n\n') + '\n[[/section]]');
            } else if (/^H[1-6]$/.test(tag)) {
                const text = oneLine(inlineOut(el.childNodes));
                if (text) out.push((tag === 'H1' || tag === 'H2' ? '## ' : '### ') + text);
            } else if (tag === 'UL' || tag === 'OL') {
                let n = 1;
                const lines = listItems(el).map((item) => (tag === 'OL' ? (n++) + '. ' : '- ') + item);
                if (lines.length) out.push(lines.join('\n'));
            } else if (tag === 'BLOCKQUOTE') {
                const lines = [];
                let author = '';
                paragraph(inlineOut(el.childNodes)).split('\n').forEach((line) => {
                    const who = line.match(/^(?:—|–|--)\s*(.+)$/);
                    if (who) author = who[1];
                    else if (line.trim()) lines.push(line.trim());
                });
                if (lines.length) out.push(lines.map((line) => '> ' + line).concat(author ? ['> — ' + author] : []).join('\n'));
            } else if (tag === 'HR') {
                // nothing
            } else if ([...el.children].some((child) => !INLINE.test(child.tagName) && !skip(child))) {
                out.push(...blocksOut(el)); // a wrapper, for example a pasted <div>
            } else {
                const text = paragraph(inlineOut(el.childNodes));
                if (text) out.push(text);
            }
        }
        flushLoose();
        return out;
    }

    function toMarks(root) {
        return blocksOut(root).join('\n\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    // Shared for testing and for other scripts.
    window.vtRich = { toHtml, toMarks };

    // ---- The editor ----

    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (error) { /* older browsers */ }

    function linkAllowed(address) {
        return /^(https?:\/\/|mailto:|tel:)/i.test(address) || /^[a-z0-9][a-z0-9\-/]*\.php([?#][^\s]*)?$/i.test(address) || address.startsWith('#');
    }

    function askLink(current = 'https://') {
        const answer = window.prompt('Link to which page? For example contact.php, or a full https:// address.', current);
        if (answer === null) return null;
        const address = answer.trim();
        if (!address || address === 'https://') return null;
        if (!linkAllowed(address)) {
            window.alert('That address can\'t be used. Use a page on this website such as contact.php, or a full address starting with https://');
            return null;
        }
        return address;
    }

    function askColour(current = 'peach') {
        const answer = window.prompt('Section colour: peach, green, gold or white', current);
        if (answer === null) return null;
        const colour = answer.trim().toLowerCase();
        return STYLES.includes(colour) ? colour : null;
    }

    document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
        const box = editor.querySelector('textarea');
        const toolbar = editor.querySelector('.rich-toolbar');
        const previewArea = editor.querySelector('.rich-preview');
        const previewButton = editor.querySelector('[data-rich="preview"]');
        const base = editor.dataset.base || '/';
        if (!box || !toolbar) return;

        const label = box.id ? document.querySelector('label[for="' + box.id + '"]') : null;
        if (label && !label.id) label.id = box.id + '-label';

        const area = document.createElement('div');
        area.className = 'rich-visual content-document rich-text';
        area.contentEditable = 'true';
        area.setAttribute('role', 'textbox');
        area.setAttribute('aria-multiline', 'true');
        if (label) area.setAttribute('aria-labelledby', label.id);
        if (box.getAttribute('aria-describedby')) area.setAttribute('aria-describedby', box.getAttribute('aria-describedby'));
        box.before(area);

        const modes = document.createElement('div');
        modes.className = 'rich-modes';
        modes.setAttribute('role', 'group');
        modes.setAttribute('aria-label', 'How to edit');
        modes.innerHTML = '<button type="button" class="rich-modes__button" data-mode="visual">Visual</button>'
            + '<button type="button" class="rich-modes__button" data-mode="text">Text with codes</button>';
        editor.prepend(modes);

        let timer = 0;
        const sync = () => {
            clearTimeout(timer);
            const text = toMarks(area);
            if (text !== box.value) {
                box.value = text;
                box.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };
        const load = () => {
            area.innerHTML = toHtml(box.value, base) || '<p><br></p>';
        };

        function setMode(mode, remember) {
            const visual = mode === 'visual';
            if (visual && editor.dataset.mode !== 'visual') load();
            if (!visual && editor.dataset.mode === 'visual') sync();
            editor.dataset.mode = mode;
            area.hidden = !visual;
            box.hidden = visual;
            if (previewButton) {
                previewButton.hidden = visual;
                if (visual && previewButton.getAttribute('aria-pressed') === 'true') previewButton.click();
            }
            if (previewArea && visual) previewArea.hidden = true;
            editor.classList.toggle('is-visual', visual);
            modes.querySelectorAll('[data-mode]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.mode === mode)));
            if (remember) {
                try { localStorage.setItem('vt-editor-mode', mode); } catch (error) { /* storage blocked */ }
            }
        }

        let start = 'visual';
        try { start = localStorage.getItem('vt-editor-mode') === 'text' ? 'text' : 'visual'; } catch (error) { /* storage blocked */ }
        setMode(start, false);

        modes.addEventListener('click', (event) => {
            const button = event.target.closest('[data-mode]');
            if (!button) return;
            setMode(button.dataset.mode, true);
            (button.dataset.mode === 'visual' ? area : box).focus();
        });

        area.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(sync, 150);
        });
        // Saved straight away when the form is sent, whatever the timer.
        box.form?.addEventListener('submit', () => { if (editor.dataset.mode === 'visual') sync(); }, true);
        // Restored from an unsaved copy (see admin.js): show it.
        box.addEventListener('vt:restored', () => { if (editor.dataset.mode === 'visual') load(); });

        // Pasting keeps the words but not the look of the other program (fonts, colours).
        area.addEventListener('paste', (event) => {
            const text = event.clipboardData?.getData('text/plain');
            if (text === undefined) return;
            event.preventDefault();
            const html = text.replace(/\r\n?/g, '\n').split(/\n{2,}/).map((part) => '<p>' + esc(part.trim()).replace(/\n/g, '<br>') + '</p>').join('');
            document.execCommand('insertHTML', false, html);
            sync();
        });

        // ---- Where the cursor is ----
        let saved = null;
        const remember = () => {
            const selection = window.getSelection();
            if (selection.rangeCount && area.contains(selection.anchorNode)) saved = selection.getRangeAt(0).cloneRange();
        };
        const restore = () => {
            area.focus();
            if (saved) {
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(saved);
            }
        };
        document.addEventListener('selectionchange', () => { if (editor.dataset.mode === 'visual') remember(); });

        /** The paragraph, heading, list or section the cursor is in (a direct child of the page or of a section). */
        function currentBlock() {
            let node = saved ? saved.startContainer : null;
            if (!node || !area.contains(node) || node === area) return null;
            while (node.parentNode && node.parentNode !== area && !(node.parentNode.hasAttribute && node.parentNode.hasAttribute('data-section'))) {
                node = node.parentNode;
            }
            return node.nodeType === 1 ? node : node.parentNode === area ? node : null;
        }

        function insertWidget(html) {
            const holder = document.createElement('div');
            holder.innerHTML = html + '<p><br></p>';
            const nodes = [...holder.childNodes];
            const block = currentBlock();
            if (block && block.parentNode) block.after(...nodes);
            else area.append(...nodes);
            const after = nodes[nodes.length - 1];
            const range = document.createRange();
            range.setStart(after, 0);
            range.collapse(true);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            area.focus();
            sync();
        }

        const command = (name, value = null) => {
            restore();
            document.execCommand(name, false, value);
            sync();
        };
        const inside = (selector) => {
            const node = saved ? saved.startContainer : null;
            const element = node && (node.nodeType === 1 ? node : node.parentElement);
            return element && area.contains(element) ? element.closest(selector) : null;
        };

        const actions = {
            bold: () => command('bold'),
            italic: () => command('italic'),
            heading: () => command('formatBlock', inside('h2, h3') ? '<p>' : '<h2>'),
            list: () => command('insertUnorderedList'),
            quote: () => command('formatBlock', inside('blockquote') ? '<p>' : '<blockquote>'),
            link: () => {
                const existing = inside('a');
                const address = askLink(existing ? existing.getAttribute('href') : 'https://');
                if (!address) return;
                restore();
                if (existing) {
                    existing.setAttribute('href', address);
                } else if (window.getSelection().isCollapsed) {
                    document.execCommand('insertHTML', false, '<a href="' + esc(address) + '">' + esc(address) + '</a>&nbsp;');
                } else {
                    document.execCommand('createLink', false, address);
                }
                sync();
            },
            section: () => {
                const section = inside('[data-section]');
                const colour = askColour(section ? section.dataset.section : 'peach');
                if (!colour) return;
                if (section) {
                    recolour(section, colour);
                } else {
                    const block = currentBlock();
                    const holder = document.createElement('div');
                    holder.innerHTML = widgets.section(colour, '');
                    const created = holder.firstChild;
                    if (block && block.parentNode) {
                        block.replaceWith(created);
                        created.querySelector('p')?.replaceWith(block);
                    } else {
                        area.append(created);
                    }
                }
                sync();
            },
            button: () => {
                const words = window.prompt('Words on the button', 'Book a consultation');
                if (!words || !words.trim()) return;
                const address = askLink('book-consultation.php');
                if (!address) return;
                insertWidget(widgets.button(words.trim().replace(/[|\]]/g, ''), address));
            },
            more: () => insertWidget(widgets.more()),
            image: async () => {
                if (!window.vtChooseImage) return;
                const keep = saved;
                const item = await window.vtChooseImage();
                saved = keep;
                if (!item) return;
                insertWidget(widgets.image(item.path, (item.caption || '').replace(/[|\]]/g, ''), base));
            },
        };

        function recolour(section, colour) {
            section.dataset.section = colour;
            section.className = 'rich-section rich-section--' + colour;
            const label = section.querySelector(':scope > .ve-section-label');
            if (label) label.firstChild.nodeValue = colour.charAt(0).toUpperCase() + colour.slice(1) + ' section';
        }

        // The toolbar belongs to this editor while it is visual (admin.js handles "Text with codes").
        toolbar.addEventListener('mousedown', (event) => { if (editor.dataset.mode === 'visual' && event.target.closest('[data-rich]')) event.preventDefault(); });
        toolbar.addEventListener('click', (event) => {
            if (editor.dataset.mode !== 'visual') return;
            const button = event.target.closest('[data-rich]');
            if (button && actions[button.dataset.rich]) actions[button.dataset.rich]();
        });
        area.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                event.preventDefault();
                actions.link();
            }
        });

        // Change or remove a picture, button, read-more point or section colour.
        area.addEventListener('click', (event) => {
            const tool = event.target.closest('[data-ve]');
            if (!tool) return;
            event.preventDefault();
            const widget = tool.closest('.ve-widget');
            const section = tool.closest('[data-section]');
            switch (tool.dataset.ve) {
                case 'remove':
                    widget?.remove();
                    break;
                case 'edit':
                    if (widget?.hasAttribute('data-image')) {
                        const caption = window.prompt('Caption under the picture (leave empty for none)', widget.dataset.caption);
                        if (caption === null) return;
                        widget.dataset.caption = caption.trim().replace(/[|\]]/g, '');
                        widget.querySelector('figcaption').innerHTML = widget.dataset.caption ? esc(widget.dataset.caption) : '<span class="ve-hint">No caption</span>';
                    } else if (widget?.hasAttribute('data-button')) {
                        const words = window.prompt('Words on the button', widget.dataset.button);
                        if (!words || !words.trim()) return;
                        const address = askLink(widget.dataset.link);
                        if (!address) return;
                        widget.outerHTML = widgets.button(words.trim().replace(/[|\]]/g, ''), address);
                    }
                    break;
                case 'colour': {
                    const colour = askColour(section.dataset.section);
                    if (colour) recolour(section, colour);
                    break;
                }
                case 'unwrap':
                    section.querySelector(':scope > .ve-section-label')?.remove();
                    section.replaceWith(...section.childNodes);
                    break;
                default:
                    return;
            }
            sync();
        });
    });
})();
