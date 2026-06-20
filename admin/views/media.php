<div class="page-head">
    <h1>Bibliothèque de médias</h1>
</div>

<form method="post" action="<?= adminUrl('media', ['action' => 'upload']) ?>" enctype="multipart/form-data" class="inline-form">
    <?= Security::csrfField() ?>
    <input type="file" name="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf" required>
    <button class="btn" type="submit">Téléverser</button>
</form>

<div class="media-grid">
    <?php foreach ($items as $item): ?>
        <div class="media-item">
            <?php if (str_starts_with($item['mime'], 'image/')): ?>
                <img src="/uploads/<?= Security::e($item['filename']) ?>" alt="<?= Security::e($item['original_name']) ?>">
            <?php else: ?>
                <div class="media-file-icon">📄</div>
            <?php endif; ?>
            <div class="media-item-name"><?= Security::e($item['original_name']) ?></div>
            <input type="text" readonly value="/uploads/<?= Security::e($item['filename']) ?>" onclick="this.select()">
            <form method="post" action="<?= adminUrl('media', ['action' => 'delete']) ?>" onsubmit="return confirm('Supprimer ce fichier ?');">
                <?= Security::csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <button class="btn-danger btn-small" type="submit">Supprimer</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><p class="muted">Aucun média pour le moment.</p><?php endif; ?>
</div>
