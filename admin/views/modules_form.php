<?php
$crumbs = [
    ['label' => 'Cours', 'url' => adminUrl('courses')],
    ['label' => $course['title'], 'url' => adminUrl('courses', ['action' => 'edit', 'id' => $course['id']])],
    ['label' => $module['id'] ? $module['title'] : 'Nouveau module'],
];
require __DIR__ . '/partials/breadcrumb.php';
?>
<h1><?= $module['id'] ? 'Modifier le module' : 'Nouveau module' ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form admin-form--wide">
    <?= Security::csrfField() ?>
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($module['title']) ?>" required>
    </label>
    <label>Position (ordre d'affichage)
        <input type="number" name="position" value="<?= (int) $module['position'] ?>">
    </label>
    <label>Description du module</label>
    <?php
        $builderName = 'description_blocks';
        $builderBlocksJson = $module['description_blocks'] ?? '[]';
        require __DIR__ . '/partials/block_builder.php';
    ?>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Annuler</a>
</form>
