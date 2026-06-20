/**
 * Vanilla JS block builder (paragraph/heading/image/video/quote/list/code/
 * divider/button/table/file/accordion/embed/spacer). Each instance manages
 * its own block array in memory, keeps a hidden input in sync as JSON so
 * the server form receives the data through a normal POST, and renders a
 * live preview pane mirroring the public-facing output. Adds Notion-style
 * inline "+" insertion between blocks, undo/redo, duplication, collapse,
 * a searchable block picker and an editor/preview/split view toggle.
 */
(function () {
    const BLOCK_TYPES = {
        paragraph: { label: 'Paragraphe', icon: '¶' },
        heading: { label: 'Titre', icon: 'H' },
        image: { label: 'Image', icon: '🖼' },
        video: { label: 'Vidéo', icon: '▶' },
        quote: { label: 'Citation', icon: '❝' },
        list: { label: 'Liste', icon: '☰' },
        code: { label: 'Code', icon: '</>' },
        button: { label: 'Bouton', icon: '⬚' },
        table: { label: 'Tableau', icon: '▦' },
        file: { label: 'Fichier', icon: '📎' },
        accordion: { label: 'Accordéon', icon: '▾' },
        embed: { label: 'Intégration', icon: '⧉' },
        spacer: { label: 'Espaceur', icon: '↕' },
        divider: { label: 'Séparateur', icon: '—' },
    };

    function emptyData(type) {
        switch (type) {
            case 'heading': return { text: '', level: 2 };
            case 'image': return { url: '', alt: '', caption: '' };
            case 'video': return { url: '' };
            case 'list': return { ordered: false, items: [''] };
            case 'code': return { code: '', language: '' };
            case 'button': return { label: '', url: '' };
            case 'table': return { rows: [['', ''], ['', '']] };
            case 'file': return { url: '', label: '' };
            case 'accordion': return { items: [{ title: '', content: '' }] };
            case 'embed': return { url: '' };
            case 'spacer': return { height: 32 };
            case 'divider': return {};
            default: return { text: '' };
        }
    }

    function fieldsHtml(block) {
        const d = block.data || {};
        switch (block.type) {
            case 'paragraph':
            case 'quote':
                return `<textarea rows="3" data-field="text" placeholder="Texte...">${escapeHtml(d.text || '')}</textarea>`;
            case 'heading':
                return `
                    <select data-field="level">
                        <option value="2" ${d.level === 2 ? 'selected' : ''}>Titre H2</option>
                        <option value="3" ${d.level === 3 ? 'selected' : ''}>Titre H3</option>
                        <option value="4" ${d.level === 4 ? 'selected' : ''}>Titre H4</option>
                    </select>
                    <input type="text" data-field="text" placeholder="Texte du titre" value="${escapeAttr(d.text || '')}">`;
            case 'image':
                return `
                    <div class="block-url-row">
                        <input type="url" data-field="url" placeholder="URL de l'image" value="${escapeAttr(d.url || '')}">
                        <button type="button" class="btn-secondary btn-small" data-action="pick-media" data-field="url">Bibliothèque</button>
                    </div>
                    <input type="text" data-field="alt" placeholder="Texte alternatif" value="${escapeAttr(d.alt || '')}">
                    <input type="text" data-field="caption" placeholder="Légende (optionnel)" value="${escapeAttr(d.caption || '')}">`;
            case 'video':
                return `<input type="url" data-field="url" placeholder="URL d'embed (YouTube/Vimeo)" value="${escapeAttr(d.url || '')}">`;
            case 'list':
                return `
                    <label class="block-inline"><input type="checkbox" data-field="ordered" ${d.ordered ? 'checked' : ''}> Liste numérotée</label>
                    <div data-field="items">
                        ${(d.items && d.items.length ? d.items : ['']).map((item, i) => `
                            <div class="block-list-item">
                                <input type="text" data-item-index="${i}" value="${escapeAttr(item)}" placeholder="Élément de liste">
                                <button type="button" class="btn-icon" data-action="remove-item">&times;</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn-secondary btn-small" data-action="add-item">+ Élément</button>`;
            case 'code':
                return `
                    <input type="text" data-field="language" placeholder="Langage (optionnel)" value="${escapeAttr(d.language || '')}">
                    <textarea rows="5" data-field="code" placeholder="Code...">${escapeHtml(d.code || '')}</textarea>`;
            case 'button':
                return `
                    <input type="text" data-field="label" placeholder="Libellé du bouton" value="${escapeAttr(d.label || '')}">
                    <input type="url" data-field="url" placeholder="URL du lien" value="${escapeAttr(d.url || '')}">`;
            case 'table': {
                const rows = d.rows && d.rows.length ? d.rows : [['', ''], ['', '']];
                return `
                    <div data-field="rows" class="block-table-editor">
                        ${rows.map((row, ri) => `
                            <div class="block-table-row">
                                ${row.map((cell, ci) => `<input type="text" data-row-index="${ri}" data-cell-index="${ci}" value="${escapeAttr(cell)}" placeholder="${ri === 0 ? 'En-tête' : 'Cellule'}">`).join('')}
                                <button type="button" class="btn-icon" data-action="remove-row">&times;</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn-secondary btn-small" data-action="add-row">+ Ligne</button>
                    <button type="button" class="btn-secondary btn-small" data-action="add-col">+ Colonne</button>`;
            }
            case 'file':
                return `
                    <div class="block-url-row">
                        <input type="url" data-field="url" placeholder="URL du fichier" value="${escapeAttr(d.url || '')}">
                        <button type="button" class="btn-secondary btn-small" data-action="pick-media" data-field="url">Bibliothèque</button>
                    </div>
                    <input type="text" data-field="label" placeholder="Libellé (ex: Télécharger le PDF)" value="${escapeAttr(d.label || '')}">`;
            case 'accordion': {
                const items = d.items && d.items.length ? d.items : [{ title: '', content: '' }];
                return `
                    <div data-field="items">
                        ${items.map((item, i) => `
                            <div class="block-accordion-item">
                                <input type="text" data-acc-index="${i}" data-acc-field="title" value="${escapeAttr(item.title || '')}" placeholder="Titre du volet">
                                <textarea rows="2" data-acc-index="${i}" data-acc-field="content" placeholder="Contenu du volet">${escapeHtml(item.content || '')}</textarea>
                                <button type="button" class="btn-icon" data-action="remove-acc-item">&times;</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn-secondary btn-small" data-action="add-acc-item">+ Volet</button>`;
            }
            case 'embed':
                return `<input type="url" data-field="url" placeholder="URL à intégrer (iframe)" value="${escapeAttr(d.url || '')}">`;
            case 'spacer':
                return `<input type="number" min="4" max="200" data-field="height" placeholder="Hauteur (px)" value="${escapeAttr(d.height || 32)}">`;
            case 'divider':
                return `<p class="muted">Ligne de séparation.</p>`;
            default:
                return '';
        }
    }

    function previewHtml(block) {
        const d = block.data || {};
        switch (block.type) {
            case 'paragraph':
                return `<p>${escapeHtml(d.text || '').replace(/\n/g, '<br>')}</p>`;
            case 'quote':
                return `<blockquote>${escapeHtml(d.text || '').replace(/\n/g, '<br>')}</blockquote>`;
            case 'heading': {
                const level = [2, 3, 4].includes(d.level) ? d.level : 2;
                return `<h${level}>${escapeHtml(d.text || '(titre vide)')}</h${level}>`;
            }
            case 'image':
                return d.url
                    ? `<figure><img src="${escapeAttr(d.url)}" alt="${escapeAttr(d.alt || '')}">${d.caption ? `<figcaption>${escapeHtml(d.caption)}</figcaption>` : ''}</figure>`
                    : '<p class="muted">(image sans URL)</p>';
            case 'video':
                return d.url ? `<div class="block-video"><iframe src="${escapeAttr(d.url)}" frameborder="0"></iframe></div>` : '<p class="muted">(vidéo sans URL)</p>';
            case 'list': {
                const items = (d.items || []).filter((i) => i.trim() !== '');
                if (!items.length) return '<p class="muted">(liste vide)</p>';
                const tag = d.ordered ? 'ol' : 'ul';
                return `<${tag}>${items.map((i) => `<li>${escapeHtml(i)}</li>`).join('')}</${tag}>`;
            }
            case 'code':
                return `<pre><code>${escapeHtml(d.code || '')}</code></pre>`;
            case 'button':
                return d.label && d.url ? `<p><a class="block-button" href="${escapeAttr(d.url)}">${escapeHtml(d.label)}</a></p>` : '<p class="muted">(bouton incomplet)</p>';
            case 'table': {
                const rows = d.rows || [];
                if (!rows.length) return '<p class="muted">(tableau vide)</p>';
                return `<table class="block-table"><tbody>${rows.map((row, i) => `<tr>${row.map((c) => `<${i === 0 ? 'th' : 'td'}>${escapeHtml(c)}</${i === 0 ? 'th' : 'td'}>`).join('')}</tr>`).join('')}</tbody></table>`;
            }
            case 'file':
                return d.url ? `<p><a class="block-file" href="${escapeAttr(d.url)}">📎 ${escapeHtml(d.label || 'Télécharger le fichier')}</a></p>` : '<p class="muted">(fichier sans URL)</p>';
            case 'accordion': {
                const items = (d.items || []).filter((i) => (i.title || '').trim() !== '');
                if (!items.length) return '<p class="muted">(accordéon vide)</p>';
                return `<div class="block-accordion">${items.map((i) => `<details><summary>${escapeHtml(i.title)}</summary><div>${escapeHtml(i.content || '').replace(/\n/g, '<br>')}</div></details>`).join('')}</div>`;
            }
            case 'embed':
                return d.url ? `<div class="block-embed"><iframe src="${escapeAttr(d.url)}" frameborder="0"></iframe></div>` : '<p class="muted">(intégration sans URL)</p>';
            case 'spacer':
                return `<div class="block-spacer" style="height:${parseInt(d.height, 10) || 32}px;border:1px dashed var(--color-border)"></div>`;
            case 'divider':
                return '<hr>';
            default:
                return '';
        }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }
    function escapeAttr(s) {
        return escapeHtml(s);
    }

    class Builder {
        constructor(root) {
            this.root = root;
            this.input = document.querySelector(root.dataset.target);
            this.blocks = [];
            try {
                this.blocks = JSON.parse(this.input.value || '[]');
            } catch (e) {
                this.blocks = [];
            }
            this.collapsed = new Set();
            this.history = [JSON.stringify(this.blocks)];
            this.historyIndex = 0;
            this.dirty = false;
            this.activePicker = null;

            this.list = root.querySelector('.block-list');
            this.addMenu = root.querySelector('.block-add-menu');
            this.preview = root.querySelector('.block-builder-preview .block-preview');
            this.toolbar = root.querySelector('.block-toolbar');
            this.countLabel = root.querySelector('.block-count');

            this.buildAddMenu();
            this.bindToolbar();
            this.render();
            this.bindEvents();
            this.bindUnloadGuard();
        }

        buildAddMenu() {
            this.addMenu.innerHTML = `<button type="button" class="btn-secondary btn-small" data-action="open-append-picker">+ Ajouter un bloc</button>`;
        }

        bindToolbar() {
            if (!this.toolbar) return;
            this.toolbar.addEventListener('click', (e) => {
                const undoBtn = e.target.closest('[data-action="undo"]');
                const redoBtn = e.target.closest('[data-action="redo"]');
                const viewBtn = e.target.closest('[data-view]');
                if (undoBtn) { this.undo(); }
                if (redoBtn) { this.redo(); }
                if (viewBtn) {
                    this.toolbar.querySelectorAll('[data-view]').forEach((b) => b.classList.remove('is-active'));
                    viewBtn.classList.add('is-active');
                    this.root.querySelector('.block-builder-layout').dataset.view = viewBtn.dataset.view;
                }
            });
        }

        bindUnloadGuard() {
            window.addEventListener('beforeunload', (e) => {
                if (!this.dirty) return;
                e.preventDefault();
                e.returnValue = '';
            });
            const form = this.root.closest('form');
            if (form) {
                form.addEventListener('submit', () => { this.dirty = false; });
            }
        }

        pushHistory() {
            const snapshot = JSON.stringify(this.blocks);
            if (snapshot === this.history[this.historyIndex]) return;
            this.history = this.history.slice(0, this.historyIndex + 1);
            this.history.push(snapshot);
            this.historyIndex = this.history.length - 1;
            this.dirty = true;
        }

        undo() {
            if (this.historyIndex <= 0) return;
            this.historyIndex -= 1;
            this.blocks = JSON.parse(this.history[this.historyIndex]);
            this.render(false);
        }

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;
            this.historyIndex += 1;
            this.blocks = JSON.parse(this.history[this.historyIndex]);
            this.render(false);
        }

        render(recordHistory = true) {
            const insertRow = (atIndex) => `<div class="block-insert-row"><button type="button" class="block-insert-btn" data-insert-at="${atIndex}" title="Insérer un bloc ici">+</button></div>`;

            this.list.innerHTML = insertRow(0) + this.blocks.map((block, index) => {
                const isCollapsed = this.collapsed.has(index);
                return `
                <div class="block-item ${isCollapsed ? 'is-collapsed' : ''}" draggable="true" data-index="${index}">
                    <div class="block-item-header">
                        <span class="block-handle" title="Déplacer">⠿</span>
                        <button type="button" class="block-collapse-toggle" data-action="toggle-collapse" title="${isCollapsed ? 'Déplier' : 'Replier'}">${isCollapsed ? '▸' : '▾'}</button>
                        <span class="block-type-label">${BLOCK_TYPES[block.type]?.icon || ''} ${BLOCK_TYPES[block.type]?.label || block.type}</span>
                        <div class="block-item-actions">
                            <button type="button" class="btn-icon" data-action="duplicate" title="Dupliquer">⎘</button>
                            <button type="button" class="btn-icon" data-action="up" title="Monter">↑</button>
                            <button type="button" class="btn-icon" data-action="down" title="Descendre">↓</button>
                            <button type="button" class="btn-icon" data-action="delete" title="Supprimer">&times;</button>
                        </div>
                    </div>
                    <div class="block-item-body" ${isCollapsed ? 'hidden' : ''}>${fieldsHtml(block)}</div>
                </div>
                ${insertRow(index + 1)}
            `;
            }).join('');

            if (!this.blocks.length) {
                this.list.insertAdjacentHTML('beforeend', '<p class="muted block-empty-hint">Aucun bloc. Cliquez sur "+" pour en ajouter un.</p>');
            }

            this.sync();
            if (recordHistory) this.pushHistory();
        }

        sync() {
            this.input.value = JSON.stringify(this.blocks);
            this.renderPreview();
            if (this.countLabel) {
                this.countLabel.textContent = this.blocks.length + (this.blocks.length === 1 ? ' bloc' : ' blocs');
            }
        }

        renderPreview() {
            if (!this.preview) return;
            this.preview.innerHTML = this.blocks.map(previewHtml).join('') || '<p class="muted">L\'aperçu apparaîtra ici.</p>';
        }

        openPicker(anchorEl, onPick) {
            this.closePicker();
            const picker = document.createElement('div');
            picker.className = 'block-picker';
            picker.innerHTML = `
                <input type="text" class="block-picker-search" placeholder="Rechercher un type de bloc...">
                <div class="block-picker-results"></div>`;
            const searchInput = picker.querySelector('.block-picker-search');
            const results = picker.querySelector('.block-picker-results');

            const renderResults = (query) => {
                const q = query.trim().toLowerCase();
                const matches = Object.entries(BLOCK_TYPES).filter(([type, info]) => !q || info.label.toLowerCase().includes(q) || type.includes(q));
                results.innerHTML = matches.map(([type, info]) =>
                    `<button type="button" class="block-picker-item" data-pick-type="${type}"><span class="block-add-icon">${info.icon}</span>${info.label}</button>`
                ).join('') || '<p class="muted">Aucun résultat.</p>';
            };
            renderResults('');

            searchInput.addEventListener('input', () => renderResults(searchInput.value));
            results.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-pick-type]');
                if (!btn) return;
                onPick(btn.dataset.pickType);
                this.closePicker();
            });

            anchorEl.insertAdjacentElement('afterend', picker);
            this.activePicker = picker;
            searchInput.focus();

            this._outsideClickHandler = (e) => {
                if (!picker.contains(e.target) && e.target !== anchorEl) this.closePicker();
            };
            document.addEventListener('click', this._outsideClickHandler, { capture: true });
        }

        closePicker() {
            if (this.activePicker) {
                this.activePicker.remove();
                this.activePicker = null;
            }
            if (this._outsideClickHandler) {
                document.removeEventListener('click', this._outsideClickHandler, { capture: true });
                this._outsideClickHandler = null;
            }
        }

        openMediaPicker(onPick) {
            const overlay = document.createElement('div');
            overlay.className = 'block-media-modal-overlay';
            overlay.innerHTML = `
                <div class="block-media-modal">
                    <div class="block-media-modal-head">
                        <strong>Choisir un média</strong>
                        <button type="button" class="btn-icon" data-action="close-media-modal">&times;</button>
                    </div>
                    <div class="block-media-modal-grid"><p class="muted">Chargement...</p></div>
                </div>`;
            document.body.appendChild(overlay);

            const close = () => overlay.remove();
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay || e.target.closest('[data-action="close-media-modal"]')) close();
            });

            fetch('/admin/?page=media&action=list')
                .then((r) => r.json())
                .then((items) => {
                    const grid = overlay.querySelector('.block-media-modal-grid');
                    if (!items.length) {
                        grid.innerHTML = '<p class="muted">Aucun média. Ajoutez-en depuis la page Médias.</p>';
                        return;
                    }
                    grid.innerHTML = items.map((item) => `
                        <button type="button" class="block-media-modal-item" data-url="${escapeAttr(item.url)}">
                            ${item.isImage ? `<img src="${escapeAttr(item.url)}" alt="">` : '<div class="media-file-icon">📄</div>'}
                            <span>${escapeHtml(item.name)}</span>
                        </button>
                    `).join('');
                    grid.addEventListener('click', (e) => {
                        const btn = e.target.closest('[data-url]');
                        if (!btn) return;
                        onPick(btn.dataset.url);
                        close();
                    });
                })
                .catch(() => {
                    overlay.querySelector('.block-media-modal-grid').innerHTML = '<p class="muted">Erreur de chargement.</p>';
                });
        }

        insertBlockAt(index, type) {
            this.blocks.splice(index, 0, { type, data: emptyData(type) });
            this.render();
        }

        bindEvents() {
            this.addMenu.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action="open-append-picker"]');
                if (!btn) return;
                this.openPicker(btn, (type) => this.insertBlockAt(this.blocks.length, type));
            });

            this.list.addEventListener('click', (e) => {
                const insertBtn = e.target.closest('[data-insert-at]');
                if (insertBtn) {
                    this.openPicker(insertBtn, (type) => this.insertBlockAt(parseInt(insertBtn.dataset.insertAt, 10), type));
                    return;
                }

                const item = e.target.closest('.block-item');
                if (!item) return;
                const index = parseInt(item.dataset.index, 10);
                const actionBtn = e.target.closest('[data-action]');
                const action = actionBtn?.dataset.action;
                const block = this.blocks[index];

                if (action === 'pick-media') {
                    this.openMediaPicker((url) => {
                        block.data[actionBtn.dataset.field] = url;
                        this.render();
                    });
                } else if (action === 'delete') {
                    this.blocks.splice(index, 1);
                    this.collapsed.delete(index);
                    this.render();
                } else if (action === 'duplicate') {
                    this.blocks.splice(index + 1, 0, JSON.parse(JSON.stringify(block)));
                    this.render();
                } else if (action === 'toggle-collapse') {
                    this.collapsed.has(index) ? this.collapsed.delete(index) : this.collapsed.add(index);
                    this.render(false);
                } else if (action === 'up' && index > 0) {
                    [this.blocks[index - 1], this.blocks[index]] = [this.blocks[index], this.blocks[index - 1]];
                    this.render();
                } else if (action === 'down' && index < this.blocks.length - 1) {
                    [this.blocks[index + 1], this.blocks[index]] = [this.blocks[index], this.blocks[index + 1]];
                    this.render();
                } else if (action === 'add-item') {
                    (block.data.items ||= []).push('');
                    this.render();
                } else if (action === 'remove-item') {
                    const row = e.target.closest('.block-list-item');
                    const idx = Array.from(row.parentElement.children).indexOf(row);
                    block.data.items.splice(idx, 1);
                    this.render();
                } else if (action === 'add-row') {
                    const cols = block.data.rows[0]?.length || 2;
                    block.data.rows.push(new Array(cols).fill(''));
                    this.render();
                } else if (action === 'remove-row') {
                    const row = e.target.closest('.block-table-row');
                    const idx = Array.from(row.parentElement.children).indexOf(row);
                    block.data.rows.splice(idx, 1);
                    this.render();
                } else if (action === 'add-col') {
                    block.data.rows.forEach((row) => row.push(''));
                    this.render();
                } else if (action === 'add-acc-item') {
                    (block.data.items ||= []).push({ title: '', content: '' });
                    this.render();
                } else if (action === 'remove-acc-item') {
                    const row = e.target.closest('.block-accordion-item');
                    const idx = Array.from(row.parentElement.children).indexOf(row);
                    block.data.items.splice(idx, 1);
                    this.render();
                }
            });

            this.list.addEventListener('input', (e) => {
                const item = e.target.closest('.block-item');
                if (!item) return;
                const index = parseInt(item.dataset.index, 10);
                const block = this.blocks[index];
                const field = e.target.dataset.field;
                const itemIndexAttr = e.target.dataset.itemIndex;
                const rowIndexAttr = e.target.dataset.rowIndex;
                const accIndexAttr = e.target.dataset.accIndex;

                if (itemIndexAttr !== undefined) {
                    block.data.items[parseInt(itemIndexAttr, 10)] = e.target.value;
                } else if (rowIndexAttr !== undefined) {
                    block.data.rows[parseInt(rowIndexAttr, 10)][parseInt(e.target.dataset.cellIndex, 10)] = e.target.value;
                } else if (accIndexAttr !== undefined) {
                    block.data.items[parseInt(accIndexAttr, 10)][e.target.dataset.accField] = e.target.value;
                } else if (field) {
                    if (e.target.type === 'checkbox') {
                        block.data[field] = e.target.checked;
                    } else if (field === 'level' || field === 'height') {
                        block.data[field] = parseInt(e.target.value, 10);
                    } else {
                        block.data[field] = e.target.value;
                    }
                }
                this.sync();
                this.dirty = true;
            });

            this.list.addEventListener('change', () => this.pushHistory());

            this.root.addEventListener('keydown', (e) => {
                const isMeta = e.ctrlKey || e.metaKey;
                if (!isMeta || e.target.closest('.block-item-body')) return;
                if (e.key.toLowerCase() === 'z' && !e.shiftKey) { e.preventDefault(); this.undo(); }
                else if (e.key.toLowerCase() === 'z' && e.shiftKey) { e.preventDefault(); this.redo(); }
            });

            let dragIndex = null;
            this.list.addEventListener('dragstart', (e) => {
                const item = e.target.closest('.block-item');
                if (!item) return;
                dragIndex = parseInt(item.dataset.index, 10);
                item.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            this.list.addEventListener('dragend', (e) => {
                const item = e.target.closest('.block-item');
                if (item) item.classList.remove('is-dragging');
                this.list.querySelectorAll('.block-item').forEach((el) => el.classList.remove('drop-before', 'drop-after'));
            });
            this.list.addEventListener('dragover', (e) => {
                e.preventDefault();
                const item = e.target.closest('.block-item');
                if (!item || dragIndex === null) return;
                this.list.querySelectorAll('.block-item').forEach((el) => el.classList.remove('drop-before', 'drop-after'));
                const rect = item.getBoundingClientRect();
                const before = (e.clientY - rect.top) < rect.height / 2;
                item.classList.add(before ? 'drop-before' : 'drop-after');
            });
            this.list.addEventListener('drop', (e) => {
                e.preventDefault();
                const item = e.target.closest('.block-item');
                if (!item || dragIndex === null) return;
                const dropIndex = parseInt(item.dataset.index, 10);
                const rect = item.getBoundingClientRect();
                const before = (e.clientY - rect.top) < rect.height / 2;
                let targetIndex = before ? dropIndex : dropIndex + 1;
                if (targetIndex === dragIndex || targetIndex === dragIndex + 1) {
                    dragIndex = null;
                    this.render(false);
                    return;
                }
                const [moved] = this.blocks.splice(dragIndex, 1);
                if (targetIndex > dragIndex) targetIndex -= 1;
                this.blocks.splice(targetIndex, 0, moved);
                dragIndex = null;
                this.render();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-builder]').forEach((root) => {
            if (!root.dataset.builderInit) {
                root.dataset.builderInit = '1';
                new Builder(root);
            }
        });
    });

    window.BlockBuilderLib = {
        init(root) {
            if (root && !root.dataset.builderInit) {
                root.dataset.builderInit = '1';
                new Builder(root);
            }
        },
    };
})();
