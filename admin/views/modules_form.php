<h1><?= $module['id'] ? 'Modifier le module' : 'Nouveau module' ?></h1>
<p class="muted">Cours : <?= Security::e($course['title']) ?></p>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($module['title']) ?>" required>
    </label>
    <label>Position (ordre d'affichage)
        <input type="number" name="position" value="<?= (int) $module['position'] ?>">
    </label>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Annuler</a>
</form>
