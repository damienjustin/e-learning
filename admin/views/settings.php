<h1>Réglages</h1>

<?php if ($saved): ?>
    <div class="alert alert-success">Réglages enregistrés.</div>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <label>Nom du site
        <input type="text" name="name" value="<?= Security::e($config['app']['name']) ?>" required>
    </label>
    <label>Thème actif
        <select name="theme">
            <?php foreach ($themes as $theme): ?>
                <option value="<?= Security::e($theme) ?>" <?= $config['app']['theme'] === $theme ? 'selected' : '' ?>><?= Security::e($theme) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn" type="submit">Enregistrer</button>
</form>

<p class="muted">Pour créer un nouveau thème, dupliquez le dossier <code>themes/default</code> sous un nouveau nom dans <code>themes/</code> et personnalisez les fichiers PHP/CSS.</p>
