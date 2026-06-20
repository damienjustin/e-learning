<div class="page-head">
    <h1>Cours</h1>
    <a class="btn" href="<?= adminUrl('courses', ['action' => 'create']) ?>">+ Nouveau cours</a>
</div>

<table class="admin-table">
    <thead><tr><th>Titre</th><th>Formateur</th><th>Statut</th><th>Prix</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($courses as $course): ?>
        <tr>
            <td><a href="<?= adminUrl('courses', ['action' => 'edit', 'id' => $course['id']]) ?>"><?= Security::e($course['title']) ?></a></td>
            <td><?= Security::e($course['instructor_name']) ?></td>
            <td><span class="badge badge-<?= Security::e($course['status']) ?>"><?= Security::e($course['status']) ?></span></td>
            <td><?= number_format((float) $course['price'], 2) ?> &euro;</td>
            <td>
                <form method="post" action="<?= adminUrl('courses', ['action' => 'delete']) ?>" onsubmit="return confirm('Supprimer ce cours ?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $course['id'] ?>">
                    <button class="btn-danger" type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$courses): ?>
        <tr><td colspan="5">Aucun cours pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
