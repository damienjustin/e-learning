/**
 * Vanilla JS block builder (paragraph/heading/image/video/quote/list/code/
 * divider/button). Each instance manages its own block array in memory
 * and keeps a hidden input in sync as JSON so the server form receives
 * the data through a normal POST, with no client-side framework involved.
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
            case 'divider': return {};
            default: return { text: '' };
        }
    }

    function fieldsHtml(block, index) {
        const d = block.data || {};
        const p = (name) => `b_${index}_${name}`;
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
            case 'divider':
                return `<p class="muted">Ligne de séparation.</p>`;
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
            this.buildAddMenu();
            this.render();
            this.bindEvents();
        }

        buildAddMenu() {
            this.addMenu.innerHTML = Object.entries(BLOCK_TYPES).map(([type, info]) =>
                `<button type="button" class="btn-secondary btn-small" data-add-type="${type}">${info.icon} ${info.label}</button>`
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
                    (this.blocks[index].data.items ||= []).push('');
                    this.render();
                } else if (action === 'remove-item') {
                    const row = e.target.closest('.block-list-item');
                    const idx = Array.from(row.parentElement.children).indexOf(row);
                    this.blocks[index].data.items.splice(idx, 1);
                    this.render();
                }
            });

            this.list.addEventListener('input', (e) => {
                const item = e.target.closest('.block-item');
                if (!item) return;
                const index = parseInt(item.dataset.index, 10);
                const field = e.target.dataset.field;
                const itemIndexAttr = e.target.dataset.itemIndex;

                if (itemIndexAttr !== undefined) {
                    this.blocks[index].data.items[parseInt(itemIndexAttr, 10)] = e.target.value;
                } else if (field) {
                    if (e.target.type === 'checkbox') {
                        this.blocks[index].data[field] = e.target.checked;
                    } else if (field === 'level') {
                        this.blocks[index].data[field] = parseInt(e.target.value, 10);
                    } else {
                        this.blocks[index].data[field] = e.target.value;
                    }
                }
                this.sync();
            });

            let dragIndex = null;
            this.list.addEventListener('dragstart', (e) => {
                const item = e.target.closest('.block-item');
                if (!item) return;
                dragIndex = parseInt(item.dataset.index, 10);
                e.dataTransfer.effectAllowed = 'move';
            });
            this.list.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            this.list.addEventListener('drop', (e) => {
                e.preventDefault();
                const item = e.target.closest('.block-item');
                if (!item || dragIndex === null) return;
                const dropIndex = parseInt(item.dataset.index, 10);
                if (dropIndex === dragIndex) return;
                const [moved] = this.blocks.splice(dragIndex, 1);
                this.blocks.splice(dropIndex, 0, moved);
                dragIndex = null;
                this.render();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-builder]').forEach((root) => new Builder(root));
    });
})();
