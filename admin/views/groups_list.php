<div class="page-head">
    <h1>Groupes</h1>
    <a class="btn" href="<?= adminUrl('groups', ['action' => 'create']) ?>">+ Nouveau groupe</a>
</div>

<form method="get" class="admin-filters">
    <input type="hidden" name="page" value="groups">
    <input type="text" name="q" placeholder="Rechercher un groupe..." value="<?= Security::e($search) ?>">
    <button class="btn-secondary" type="submit">Rechercher</button>
</form>

<table class="admin-table">
    <thead><tr><th>Nom</th><th>Membres</th><th>Cours liés</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($groups as $group): ?>
        <tr>
            <td><a href="<?= adminUrl('groups', ['action' => 'edit', 'id' => $group['id']]) ?>"><?= Security::e($group['name']) ?></a></td>
            <td><?= (int) $group['member_count'] ?></td>
            <td><?= (int) $group['course_count'] ?></td>
            <td>
                <form method="post" action="<?= adminUrl('groups', ['action' => 'delete']) ?>" onsubmit="return confirm('Supprimer ce groupe ?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
                    <button class="btn-danger" type="submit">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$groups): ?>
        <tr><td colspan="4">Aucun groupe pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
