<div class="page-head">
    <h1>Certificat — <?= Security::e($course['title']) ?></h1>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Retour au cours</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<div class="certificate-builder">
    <form method="post" id="certificate-form" class="admin-form certificate-builder-form">
        <?= Security::csrfField() ?>
        <label>
            <input type="checkbox" name="certificate_enabled" <?= $course['certificate_enabled'] ? 'checked' : '' ?>>
            Activer le certificat pour ce cours
        </label>

        <label>Titre
            <input type="text" name="title" value="<?= Security::e($config['title']) ?>">
        </label>
        <label>Couleur du titre
            <input type="color" name="title_color" value="<?= Security::e($config['title_color']) ?>">
        </label>

        <label>Texte principal (utilisez {{student_name}}, {{course_title}}, {{date}})
            <textarea name="body" rows="5"><?= Security::e($config['body']) ?></textarea>
        </label>
        <label>Couleur du texte
            <input type="color" name="body_color" value="<?= Security::e($config['body_color']) ?>">
        </label>

        <label>Pied de page (utilisez {{instructor_name}})
            <input type="text" name="footer" value="<?= Security::e($config['footer']) ?>">
        </label>
        <label>Couleur du pied de page
            <input type="color" name="footer_color" value="<?= Security::e($config['footer_color']) ?>">
        </label>

        <label>Couleur de fond
            <input type="color" name="background_color" value="<?= Security::e($config['background_color']) ?>">
        </label>
        <label>Couleur de la bordure
            <input type="color" name="border_color" value="<?= Security::e($config['border_color']) ?>">
        </label>

        <label>Logo (URL)
            <div class="block-url-row">
                <input type="text" name="logo_url" id="cert-logo-url" value="<?= Security::e($config['logo_url']) ?>" placeholder="/uploads/logo.png">
                <button type="button" class="btn-secondary" id="cert-pick-logo">Bibliothèque</button>
            </div>
        </label>

        <button class="btn" type="submit">Enregistrer</button>
    </form>

    <div class="certificate-builder-preview">
        <h2>Aperçu</h2>
        <img id="certificate-preview-img" src="" alt="Aperçu du certificat">
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('certificate-form');
    const img = document.getElementById('certificate-preview-img');
    const previewUrl = <?= json_encode(adminUrl('certificate', ['action' => 'preview', 'id' => $course['id']])) ?>;
    let timer = null;

    function refreshPreview() {
        const data = new FormData(form);
        fetch(previewUrl, { method: 'POST', body: data })
            .then((r) => r.blob())
            .then((blob) => {
                if (img.dataset.prevUrl) {
                    URL.revokeObjectURL(img.dataset.prevUrl);
                }
                const url = URL.createObjectURL(blob);
                img.src = url;
                img.dataset.prevUrl = url;
            });
    }

    form.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(refreshPreview, 400);
    });

    document.getElementById('cert-pick-logo').addEventListener('click', () => {
        fetch('/admin/?page=media&action=list')
            .then((r) => r.json())
            .then((items) => {
                const overlay = document.createElement('div');
                overlay.className = 'block-media-modal-overlay';
                overlay.innerHTML = `<div class="block-media-modal">
                    <div class="block-media-modal-head"><strong>Choisir une image</strong><button type="button" class="btn-link" data-close>Fermer</button></div>
                    <div class="block-media-modal-grid"></div>
                </div>`;
                const grid = overlay.querySelector('.block-media-modal-grid');
                items.filter((i) => i.isImage).forEach((i) => {
                    const el = document.createElement('div');
                    el.className = 'block-media-modal-item';
                    el.dataset.url = i.url;
                    el.innerHTML = `<img src="${i.url}" alt=""><span>${i.name}</span>`;
                    el.addEventListener('click', () => {
                        document.getElementById('cert-logo-url').value = i.url;
                        overlay.remove();
                        refreshPreview();
                    });
                    grid.appendChild(el);
                });
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay || e.target.closest('[data-close]')) {
                        overlay.remove();
                    }
                });
                document.body.appendChild(overlay);
            });
    });

    refreshPreview();
})();
</script>
