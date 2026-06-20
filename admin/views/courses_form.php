<h1><?= $course['id'] ? 'Modifier le cours' : 'Nouveau cours' ?></h1>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($course['title']) ?>" required>
    </label>
    <label>Slug (URL, laisser vide pour générer automatiquement)
        <input type="text" name="slug" value="<?= Security::e($course['slug']) ?>">
    </label>
    <label>Résumé
        <textarea name="summary" rows="2"><?= Security::e($course['summary']) ?></textarea>
    </label>
    <label>Description complète
        <textarea name="description" rows="6"><?= Security::e($course['description']) ?></textarea>
    </label>
    <label>Prix (&euro;)
        <input type="number" step="0.01" min="0" name="price" value="<?= Security::e((string) $course['price']) ?>">
    </label>
    <label>Statut
        <select name="status">
            <?php foreach (['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $course['status'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="btn" type="submit">Enregistrer</button>
</form>

<?php if ($course['id']): ?>
    <h2>Modules &amp; leçons</h2>
    <a class="btn-secondary" href="<?= adminUrl('modules', ['action' => 'create', 'course_id' => $course['id']]) ?>">+ Ajouter un module</a>

    <?php foreach ($modules as $module): ?>
        <div class="module-block">
            <div class="page-head">
                <h3><?= Security::e($module['title']) ?></h3>
                <div>
                    <a class="btn-secondary" href="<?= adminUrl('modules', ['action' => 'edit', 'id' => $module['id'], 'course_id' => $course['id']]) ?>">Modifier</a>
                    <form method="post" action="<?= adminUrl('modules', ['action' => 'delete', 'course_id' => $course['id']]) ?>" style="display:inline" onsubmit="return confirm('Supprimer ce module et son contenu ?');">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="id" value="<?= (int) $module['id'] ?>">
                        <button class="btn-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            </div>

            <ul class="lesson-admin-list">
                <?php foreach ($module['lessons'] as $lesson): ?>
                    <li>
                        📄 <a href="<?= adminUrl('lessons', ['action' => 'edit', 'id' => $lesson['id'], 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>"><?= Security::e($lesson['title']) ?></a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($module['quizzes'] as $quiz): ?>
                    <li>
                        📝 <a href="<?= adminUrl('quizzes', ['action' => 'edit', 'id' => $quiz['id'], 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">Quiz: <?= Security::e($quiz['title']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn-link" href="<?= adminUrl('lessons', ['action' => 'create', 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">+ Leçon</a>
            <a class="btn-link" href="<?= adminUrl('quizzes', ['action' => 'create', 'module_id' => $module['id'], 'course_id' => $course['id']]) ?>">+ Quiz</a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="muted">Enregistrez le cours pour pouvoir ajouter des modules et des leçons.</p>
<?php endif; ?>
