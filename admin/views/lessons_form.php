<h1><?= $lesson['id'] ? 'Modifier la leçon' : 'Nouvelle leçon' ?></h1>
<p class="muted">Cours : <?= Security::e($course['title']) ?> &rsaquo; Module : <?= Security::e($module['title']) ?></p>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= Security::e($err) ?></div>
<?php endforeach; ?>

<form method="post" class="admin-form">
    <?= Security::csrfField() ?>
    <label>Titre
        <input type="text" name="title" value="<?= Security::e($lesson['title']) ?>" required>
    </label>
    <label>Slug
        <input type="text" name="slug" value="<?= Security::e($lesson['slug']) ?>">
    </label>
    <label>Type de contenu
        <select name="content_type" id="content_type">
            <?php foreach (['text' => 'Texte', 'video' => 'Vidéo', 'file' => 'Fichier'] as $value => $label): ?>
                <option value="<?= $value ?>" <?= $lesson['content_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>URL vidéo (YouTube/Vimeo embed)
        <input type="url" name="video_url" value="<?= Security::e($lesson['video_url']) ?>">
    </label>
    <label>Contenu (HTML autorisé pour la mise en forme)
        <textarea name="content" rows="10"><?= Security::e($lesson['content']) ?></textarea>
    </label>
    <label>Durée estimée (minutes)
        <input type="number" name="duration_minutes" value="<?= Security::e((string) ($lesson['duration_minutes'] ?? '')) ?>">
    </label>
    <label>Position (ordre)
        <input type="number" name="position" value="<?= (int) $lesson['position'] ?>">
    </label>
    <button class="btn" type="submit">Enregistrer</button>
    <a class="btn-secondary" href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>">Annuler</a>
</form>
