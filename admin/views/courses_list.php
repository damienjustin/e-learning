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

<table class="admin-table">
    <thead><tr><th>Titre</th><th>Formateur</th><th>Statut</th><th>Accès</th><th>Inscrits</th><th>Prix</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($courses as $course): ?>
        <tr>
            <td><a href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>"><?= Security::e($course['title']) ?></a></td>
            <td><?= Security::e($course['instructor_name']) ?></td>
            <td><span class="badge badge-<?= Security::e($course['status']) ?>"><?= Security::e($course['status']) ?></span></td>
            <td><?= $course['visibility'] === 'restricted' ? 'Restreint' : 'Public' ?></td>
            <td><?= (int) $course['enrollment_count'] ?></td>
            <td><?= number_format((float) $course['price'], 2) ?> &euro;</td>
            <td>
                <form method="post" action="<?= adminUrl('courses', ['action' => 'duplicate']) ?>" style="display:inline">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                    <button class="btn-secondary" type="submit">Dupliquer</button>
                </form>
                <form method="post" action="<?= adminUrl('courses', ['action' => 'delete']) ?>" style="display:inline" onsubmit="return confirm('Supprimer ce cours ?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                    <button class="btn-danger" type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$courses): ?>
        <tr><td colspan="7">Aucun cours pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
