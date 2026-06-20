/**
 * Vanilla JS block builder (paragraph/heading/image/video/quote/list/code/
 * divider/button/table/file/accordion/embed/spacer). Each instance manages
 * its own block array in memory, keeps a hidden input in sync as JSON so
 * the server form receives the data through a normal POST, and renders a
 * live preview pane mirroring the public-facing output.
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

    function fieldsHtml(block, index) {
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
                    <input type="url" data-field="url" placeholder="URL de l'image" value="${escapeAttr(d.url || '')}">
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
                    <input type="url" data-field="url" placeholder="URL du fichier" value="${escapeAttr(d.url || '')}">
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
            this.list = root.querySelector('.block-list');
            this.addMenu = root.querySelector('.block-add-menu');
            this.preview = root.querySelector('.block-preview');
            this.buildAddMenu();
            this.render();
            this.bindEvents();
        }

        buildAddMenu() {
            this.addMenu.innerHTML = Object.entries(BLOCK_TYPES).map(([type, info]) =>
                `<button type="button" class="block-add-btn" data-add-type="${type}"><span class="block-add-icon">${info.icon}</span><span>${info.label}</span></button>`
            ).join('');
        }

        render() {
            this.list.innerHTML = this.blocks.map((block, index) => `
                <div class="block-item" draggable="true" data-index="${index}">
                    <div class="block-item-header">
                        <span class="block-handle" title="Déplacer">⠿</span>
                        <span class="block-type-label">${BLOCK_TYPES[block.type]?.icon || ''} ${BLOCK_TYPES[block.type]?.label || block.type}</span>
                        <div class="block-item-actions">
                            <button type="button" class="btn-icon" data-action="up" title="Monter">↑</button>
                            <button type="button" class="btn-icon" data-action="down" title="Descendre">↓</button>
                            <button type="button" class="btn-icon" data-action="delete" title="Supprimer">&times;</button>
                        </div>
                    </div>
                    <div class="block-item-body">${fieldsHtml(block, index)}</div>
                </div>
            `).join('') || '<p class="muted">Aucun bloc. Ajoutez-en un ci-dessous.</p>';
            this.sync();
        }

        sync() {
            this.input.value = JSON.stringify(this.blocks);
            this.renderPreview();
        }

        renderPreview() {
            if (!this.preview) return;
            this.preview.innerHTML = this.blocks.map(previewHtml).join('') || '<p class="muted">L\'aperçu apparaîtra ici.</p>';
        }

        bindEvents() {
            this.addMenu.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-add-type]');
                if (!btn) return;
                const type = btn.dataset.addType;
                this.blocks.push({ type, data: emptyData(type) });
                this.render();
            });

            this.list.addEventListener('click', (e) => {
                const item = e.target.closest('.block-item');
                if (!item) return;
                const index = parseInt(item.dataset.index, 10);
                const action = e.target.closest('[data-action]')?.dataset.action;
                const block = this.blocks[index];

                if (action === 'delete') {
                    this.blocks.splice(index, 1);
                    this.render();
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
                    this.render();
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
