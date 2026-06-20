<div class="page-head">
    <h1>Cours</h1>
    <a class="btn" href="<?= adminUrl('courses', ['action' => 'create']) ?>">+ Nouveau cours</a>
</div>

<form method="get" class="admin-filters">
    <input type="hidden" name="page" value="courses">
    <input type="text" name="q" placeholder="Rechercher un cours..." value="<?= Security::e($search) ?>">
    <select name="status">
        <option value="">Tous les statuts</option>
        <?php foreach (['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn-secondary" type="submit">Filtrer</button>
</form>

<form method="post" action="<?= adminUrl('courses', ['action' => 'bulk']) ?>" id="bulk-form">
    <?= Security::csrfField() ?>
    <div class="admin-filters">
        <select name="bulk_action">
            <option value="publish">Publier</option>
            <option value="draft">Mettre en brouillon</option>
            <option value="archive">Archiver</option>
            <option value="delete">Supprimer</option>
        </select>
        <button class="btn-secondary" type="submit" onclick="return confirm('Appliquer cette action aux cours sélectionnés ?');">Appliquer à la sélection</button>
    </div>

    <table class="admin-table">
        <thead><tr><th><input type="checkbox" id="select-all-courses"></th><th>Titre</th><th>Formateur</th><th>Statut</th><th>Accès</th><th>Inscrits</th><th>Prix</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><input type="checkbox" name="ids[]" value="<?= (int) $course['id'] ?>" class="course-select"></td>
                <td><a href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>"><?= Security::e($course['title']) ?></a></td>
                <td><?= Security::e($course['instructor_name']) ?></td>
                <td><span class="badge badge-<?= Security::e($course['status']) ?>"><?= Security::e($course['status']) ?></span></td>
                <td><?= $course['visibility'] === 'restricted' ? 'Restreint' : 'Public' ?></td>
                <td><?= (int) $course['enrollment_count'] ?></td>
                <td><?= number_format((float) $course['price'], 2) ?> &euro;</td>
                <td>
                    <button class="btn-secondary" type="submit" form="duplicate-<?= (int) $course['id'] ?>">Dupliquer</button>
                    <button class="btn-danger" type="submit" form="delete-<?= (int) $course['id'] ?>" onclick="return confirm('Supprimer ce cours ?');">Supprimer</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?>
            <tr><td colspan="8">Aucun cours pour le moment.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</form>

<?php foreach ($courses as $course): ?>
    <form id="duplicate-<?= (int) $course['id'] ?>" method="post" action="<?= adminUrl('courses', ['action' => 'duplicate']) ?>" style="display:none">
        <?= Security::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
    </form>
    <form id="delete-<?= (int) $course['id'] ?>" method="post" action="<?= adminUrl('courses', ['action' => 'delete']) ?>" style="display:none">
        <?= Security::csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
    </form>
<?php endforeach; ?>

<script>
document.getElementById('select-all-courses').addEventListener('change', function (e) {
    document.querySelectorAll('.course-select').forEach(function (cb) { cb.checked = e.target.checked; });
});
</script>
