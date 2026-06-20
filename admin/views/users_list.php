<div class="page-head">
    <h1>Utilisateurs</h1>
    <a class="btn" href="<?= adminUrl('users', ['action' => 'create']) ?>">+ Nouvel utilisateur</a>
</div>

<table class="admin-table">
    <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><a href="<?= adminUrl('users', ['action' => 'edit', 'id' => $u['id']]) ?>"><?= Security::e($u['name']) ?></a></td>
            <td><?= Security::e($u['email']) ?></td>
            <td><?= Security::e($u['role']) ?></td>
            <td><?= Security::e($u['status']) ?></td>
            <td>
                <?php if ((int) $u['id'] !== Auth::id()): ?>
                <form method="post" action="<?= adminUrl('users', ['action' => 'delete']) ?>" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn-danger" type="submit">Supprimer</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
